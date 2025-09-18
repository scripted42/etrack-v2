<?php

namespace App\Services;

use App\Models\User;
use App\Models\MfaOtp;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use Illuminate\Http\Request;

class MFAService
{
    /**
     * Generate OTP for user
     */
    public function generateOTP(User $user, Request $request = null): string
    {
        // Delete existing valid OTPs for this user
        $user->mfaOtps()->valid()->delete();

        // Generate 6-digit OTP
        $otp = str_pad(random_int(100000, 999999), 6, '0', STR_PAD_LEFT);

        // Create OTP record
        $mfaOtp = $user->mfaOtps()->create([
            'otp_code' => $otp,
            'expires_at' => Carbon::now()->addMinutes(5), // 5 minutes expiry
            'ip_address' => $request ? $request->ip() : null,
        ]);

        Log::info('MFA OTP generated', [
            'user_id' => $user->id,
            'username' => $user->username,
            'ip' => $request ? $request->ip() : null,
        ]);

        return $otp;
    }

    /**
     * Verify OTP for user
     */
    public function verifyOTP(User $user, string $otp, Request $request = null): bool
    {
        $mfaOtp = $user->mfaOtps()
            ->where('otp_code', $otp)
            ->valid()
            ->first();

        if (!$mfaOtp) {
            Log::warning('MFA OTP verification failed', [
                'user_id' => $user->id,
                'username' => $user->username,
                'otp' => $otp,
                'ip' => $request ? $request->ip() : null,
            ]);
            return false;
        }

        // Mark OTP as used
        $mfaOtp->markAsUsed();

        Log::info('MFA OTP verified successfully', [
            'user_id' => $user->id,
            'username' => $user->username,
            'ip' => $request ? $request->ip() : null,
        ]);

        return true;
    }

    /**
     * Send OTP via email
     */
    public function sendOTPEmail(User $user, string $otp): bool
    {
        try {
            Mail::raw("Kode OTP Anda: {$otp}\n\nKode ini berlaku selama 5 menit.\n\nJangan bagikan kode ini kepada siapapun.", function ($message) use ($user) {
                $message->to($user->email)
                        ->subject('Kode OTP - E-Track SMPN 14 Surabaya');
            });

            Log::info('MFA OTP email sent', [
                'user_id' => $user->id,
                'username' => $user->username,
                'email' => $user->email,
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error('Failed to send MFA OTP email', [
                'user_id' => $user->id,
                'username' => $user->username,
                'email' => $user->email,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Check if MFA is enabled for user
     */
    public function isMFAEnabled(User $user): bool
    {
        return $user->isMfaEnabled();
    }

    /**
     * Enable MFA for user
     */
    public function enableMFA(User $user): bool
    {
        try {
            $user->enableMfa();
            
            Log::info('MFA enabled for user', [
                'user_id' => $user->id,
                'username' => $user->username,
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error('Failed to enable MFA', [
                'user_id' => $user->id,
                'username' => $user->username,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Disable MFA for user
     */
    public function disableMFA(User $user): bool
    {
        try {
            $user->disableMfa();
            
            Log::info('MFA disabled for user', [
                'user_id' => $user->id,
                'username' => $user->username,
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error('Failed to disable MFA', [
                'user_id' => $user->id,
                'username' => $user->username,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Clean up expired OTPs
     */
    public function cleanupExpiredOTPs(): int
    {
        $deleted = MfaOtp::expired()->delete();
        
        Log::info('Expired MFA OTPs cleaned up', [
            'deleted_count' => $deleted,
        ]);

        return $deleted;
    }

    /**
     * Get MFA statistics
     */
    public function getStatistics(): array
    {
        $totalUsers = User::count();
        $mfaEnabledUsers = User::where('mfa_enabled', true)->count();
        $totalOtps = MfaOtp::count();
        $validOtps = MfaOtp::valid()->count();
        $expiredOtps = MfaOtp::expired()->count();

        return [
            'total_users' => $totalUsers,
            'mfa_enabled_users' => $mfaEnabledUsers,
            'mfa_enabled_percentage' => $totalUsers > 0 ? round(($mfaEnabledUsers / $totalUsers) * 100, 2) : 0,
            'total_otps' => $totalOtps,
            'valid_otps' => $validOtps,
            'expired_otps' => $expiredOtps,
        ];
    }
}
