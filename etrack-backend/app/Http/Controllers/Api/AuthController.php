<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\AuditLog;
use App\Services\AuditService;
use App\Services\PasswordPolicyService;
use App\Services\SecurityMonitoringService;
use App\Rules\StrongPassword;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    /**
     * Login user and create token
     */
    public function login(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'username' => 'required|string',
            'password' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        $user = User::where('username', $request->username)
            ->orWhere('email', $request->username)
            ->first();

        // Check if account is locked
        if ($user && SecurityMonitoringService::checkAccountLockout($user)) {
            throw ValidationException::withMessages([
                'username' => ['Akun terkunci. Coba lagi dalam ' . $user->locked_until->diffForHumans()],
            ]);
        }

        if (!$user || !Hash::check($request->password, $user->password)) {
            // Monitor failed login attempt
            SecurityMonitoringService::monitorFailedLogin($request->username, $request->ip(), $request);
            
            // Increment failed attempts for existing user
            if ($user) {
                $user->increment('failed_login_attempts');
                
                // Lock account after 5 failed attempts
                if ($user->failed_login_attempts >= 5) {
                    SecurityMonitoringService::lockAccount($user);
                }
            }

            throw ValidationException::withMessages([
                'username' => ['Kredensial tidak valid.'],
            ]);
        }

        if ($user->status !== 'aktif') {
            throw ValidationException::withMessages([
                'username' => ['Akun tidak aktif.'],
            ]);
        }

        // Reset failed attempts on successful login
        if ($user->failed_login_attempts > 0) {
            $user->update(['failed_login_attempts' => 0, 'locked_until' => null]);
        }

        // Update last login
        $user->update(['last_login' => now()]);

        // Create token
        $token = $user->createToken('auth-token')->plainTextToken;

        // Log successful login
        AuditService::logAuth('LOGIN_SUCCESS', $user, $request, [
            'username' => $user->username,
            'role' => $user->role->name ?? 'unknown'
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Login berhasil',
            'data' => [
                'user' => $user->load('role'),
                'token' => $token,
            ]
        ]);
    }

    /**
     * Logout user and revoke token
     */
    public function logout(Request $request): JsonResponse
    {
        $user = $request->user();

        // Log logout
        AuditService::logAuth('LOGOUT', $user, $request, [
            'username' => $user->username,
            'role' => $user->role->name ?? 'unknown'
        ]);

        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'success' => true,
            'message' => 'Logout berhasil'
        ]);
    }

    /**
     * Get authenticated user
     */
    public function me(Request $request): JsonResponse
    {
        $user = $request->user()->load(['role', 'student', 'employee']);

        return response()->json([
            'success' => true,
            'data' => $user
        ]);
    }

    /**
     * Change password
     */
    public function changePassword(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'current_password' => 'required|string',
            'new_password' => ['required', 'string', 'confirmed', new StrongPassword],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        $user = $request->user();

        if (!Hash::check($request->current_password, $user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Password lama tidak sesuai'
            ], 400);
        }

        $user->update([
            'password' => Hash::make($request->new_password)
        ]);

        // Log password change
        AuditLog::create([
            'user_id' => $user->id,
            'action' => 'PASSWORD_CHANGED',
            'details' => ['username' => $user->username],
            'ip_address' => $request->ip(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Password berhasil diubah'
        ]);
    }

    /**
     * Validate password strength
     */
    public function validatePassword(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'password' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        $validation = PasswordPolicyService::validatePassword($request->password);
        
        return response()->json([
            'success' => true,
            'data' => [
                'is_valid' => $validation['is_valid'],
                'errors' => $validation['errors'],
                'strength' => $validation['strength'],
                'strength_description' => PasswordPolicyService::getStrengthDescription($validation['strength']),
                'suggestion' => $validation['is_valid'] ? null : PasswordPolicyService::generateSuggestion()
            ]
        ]);
    }

    /**
     * Refresh authentication token
     */
    public function refreshToken(Request $request): JsonResponse
    {
        $user = $request->user();
        
        // Revoke old tokens
        $user->tokens()->delete();
        
        // Create new token
        $token = $user->createToken('auth-token')->plainTextToken;
        
        // Log token refresh
        AuditService::logAuth('TOKEN_REFRESHED', $user, $request, [
            'username' => $user->username,
            'role' => $user->role->name ?? 'unknown'
        ]);
        
        return response()->json([
            'success' => true,
            'message' => 'Token berhasil diperbarui',
            'data' => [
                'token' => $token
            ]
        ]);
    }

    /**
     * Get security statistics
     */
    public function getSecurityStats(Request $request): JsonResponse
    {
        $stats = SecurityMonitoringService::getSecurityStats();
        
        return response()->json([
            'success' => true,
            'data' => $stats
        ]);
    }

    /**
     * Unlock user account (admin only)
     */
    public function unlockAccount(Request $request, int $userId): JsonResponse
    {
        $user = User::findOrFail($userId);
        
        SecurityMonitoringService::unlockAccount($user);
        
        // Log admin action
        AuditService::logSecurity('ACCOUNT_UNLOCKED_BY_ADMIN', [
            'target_user_id' => $userId,
            'target_username' => $user->username,
            'admin_user_id' => $request->user()->id,
            'admin_username' => $request->user()->username,
            'timestamp' => now()->toISOString()
        ], $request->user(), $request);
        
        return response()->json([
            'success' => true,
            'message' => 'Akun berhasil dibuka'
        ]);
    }
}
