<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\MFAService;
use App\Services\AuditService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class MFAController extends Controller
{
    protected $mfaService;

    public function __construct(MFAService $mfaService)
    {
        $this->mfaService = $mfaService;
    }

    /**
     * Request OTP for MFA
     */
    public function requestOTP(Request $request): JsonResponse
    {
        $user = $request->user();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'User not authenticated'
            ], 401);
        }

        if (!$this->mfaService->isMFAEnabled($user)) {
            return response()->json([
                'success' => false,
                'message' => 'MFA is not enabled for this user'
            ], 400);
        }

        try {
            // Generate OTP
            $otp = $this->mfaService->generateOTP($user, $request);

            // Send OTP via email
            $emailSent = $this->mfaService->sendOTPEmail($user, $otp);

            if (!$emailSent) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to send OTP email'
                ], 500);
            }

            // Log audit
            AuditService::logAuth('MFA_OTP_REQUESTED', $user, $request, [
                'username' => $user->username,
                'email' => $user->email
            ]);

            return response()->json([
                'success' => true,
                'message' => 'OTP telah dikirim ke email Anda'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan saat mengirim OTP'
            ], 500);
        }
    }

    /**
     * Verify OTP for MFA
     */
    public function verifyOTP(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'otp' => 'required|string|size:6'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        $user = $request->user();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'User not authenticated'
            ], 401);
        }

        try {
            $isValid = $this->mfaService->verifyOTP($user, $request->otp, $request);

            if (!$isValid) {
                // Log audit
                AuditService::logAuth('MFA_OTP_VERIFICATION_FAILED', $user, $request, [
                    'username' => $user->username,
                    'otp' => $request->otp
                ]);

                return response()->json([
                    'success' => false,
                    'message' => 'Kode OTP tidak valid atau sudah expired'
                ], 400);
            }

            // Log audit
            AuditService::logAuth('MFA_OTP_VERIFIED', $user, $request, [
                'username' => $user->username
            ]);

            return response()->json([
                'success' => true,
                'message' => 'OTP berhasil diverifikasi'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan saat verifikasi OTP'
            ], 500);
        }
    }

    /**
     * Enable MFA for user
     */
    public function enableMFA(Request $request): JsonResponse
    {
        $user = $request->user();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'User not authenticated'
            ], 401);
        }

        try {
            $enabled = $this->mfaService->enableMFA($user);

            if (!$enabled) {
                return response()->json([
                    'success' => false,
                    'message' => 'Gagal mengaktifkan MFA'
                ], 500);
            }

            // Log audit
            AuditService::logAuth('MFA_ENABLED', $user, $request, [
                'username' => $user->username
            ]);

            return response()->json([
                'success' => true,
                'message' => 'MFA berhasil diaktifkan'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan saat mengaktifkan MFA'
            ], 500);
        }
    }

    /**
     * Disable MFA for user
     */
    public function disableMFA(Request $request): JsonResponse
    {
        $user = $request->user();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'User not authenticated'
            ], 401);
        }

        try {
            $disabled = $this->mfaService->disableMFA($user);

            if (!$disabled) {
                return response()->json([
                    'success' => false,
                    'message' => 'Gagal menonaktifkan MFA'
                ], 500);
            }

            // Log audit
            AuditService::logAuth('MFA_DISABLED', $user, $request, [
                'username' => $user->username
            ]);

            return response()->json([
                'success' => true,
                'message' => 'MFA berhasil dinonaktifkan'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan saat menonaktifkan MFA'
            ], 500);
        }
    }

    /**
     * Get MFA status for user
     */
    public function getMFAStatus(Request $request): JsonResponse
    {
        $user = $request->user();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'User not authenticated'
            ], 401);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'mfa_enabled' => $this->mfaService->isMFAEnabled($user),
                'email' => $user->email
            ]
        ]);
    }

    /**
     * Get MFA statistics (admin only)
     */
    public function getStatistics(Request $request): JsonResponse
    {
        $user = $request->user();

        if (!$user || $user->role->name !== 'Admin') {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized access'
            ], 403);
        }

        try {
            $statistics = $this->mfaService->getStatistics();

            return response()->json([
                'success' => true,
                'data' => $statistics
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan saat mengambil statistik MFA'
            ], 500);
        }
    }
}
