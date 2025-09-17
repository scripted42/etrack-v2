<?php

namespace App\Services;

use App\Models\User;
use App\Models\AuditLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Cache;

class SecurityMonitoringService
{
    /**
     * Monitor failed login attempts
     */
    public static function monitorFailedLogin(string $username, string $ip, Request $request): void
    {
        $key = "failed_login_{$ip}_{$username}";
        $attempts = Cache::get($key, 0) + 1;
        
        Cache::put($key, $attempts, 300); // 5 minutes
        
        // Log security event
        AuditService::logSecurity('LOGIN_FAILED', [
            'username' => $username,
            'ip_address' => $ip,
            'attempts' => $attempts,
            'severity' => $attempts >= 3 ? 'high' : 'medium',
            'user_agent' => $request->userAgent()
        ], null, $request);
        
        // Alert if too many attempts
        if ($attempts >= 5) {
            self::sendSecurityAlert('Multiple failed login attempts', [
                'username' => $username,
                'ip_address' => $ip,
                'attempts' => $attempts,
                'timestamp' => now()->toISOString()
            ]);
        }
    }
    
    /**
     * Monitor suspicious activities
     */
    public static function monitorSuspiciousActivity(User $user, string $activity, array $details = []): void
    {
        $suspiciousPatterns = [
            'bulk_delete', 'mass_import', 'export_all', 'admin_access',
            'permission_change', 'role_change', 'password_reset'
        ];
        
        $isSuspicious = false;
        foreach ($suspiciousPatterns as $pattern) {
            if (strpos(strtolower($activity), $pattern) !== false) {
                $isSuspicious = true;
                break;
            }
        }
        
        if ($isSuspicious) {
            AuditService::logSecurity('SUSPICIOUS_ACTIVITY', array_merge([
                'activity' => $activity,
                'user_id' => $user->id,
                'username' => $user->username,
                'severity' => 'high',
                'timestamp' => now()->toISOString()
            ], $details), $user);
            
            self::sendSecurityAlert('Suspicious activity detected', [
                'user' => $user->username,
                'activity' => $activity,
                'details' => $details
            ]);
        }
    }
    
    /**
     * Monitor data access patterns
     */
    public static function monitorDataAccess(User $user, string $resource, int $recordCount): void
    {
        $key = "data_access_{$user->id}_{$resource}";
        $accessCount = Cache::get($key, 0) + 1;
        
        Cache::put($key, $accessCount, 3600); // 1 hour
        
        // Alert if accessing too many records
        if ($recordCount > 1000) {
            AuditService::logSecurity('BULK_DATA_ACCESS', [
                'user_id' => $user->id,
                'username' => $user->username,
                'resource' => $resource,
                'record_count' => $recordCount,
                'access_count' => $accessCount,
                'severity' => 'medium',
                'timestamp' => now()->toISOString()
            ], $user);
        }
    }
    
    /**
     * Check for account lockout
     */
    public static function checkAccountLockout(User $user): bool
    {
        if ($user->failed_login_attempts >= 5) {
            if ($user->locked_until && $user->locked_until > now()) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Lock account after failed attempts
     */
    public static function lockAccount(User $user, int $minutes = 30): void
    {
        $user->update([
            'failed_login_attempts' => 5,
            'locked_until' => now()->addMinutes($minutes)
        ]);
        
        AuditService::logSecurity('ACCOUNT_LOCKED', [
            'user_id' => $user->id,
            'username' => $user->username,
            'locked_until' => $user->locked_until,
            'reason' => 'Multiple failed login attempts',
            'severity' => 'high',
            'timestamp' => now()->toISOString()
        ], $user);
    }
    
    /**
     * Unlock account
     */
    public static function unlockAccount(User $user): void
    {
        $user->update([
            'failed_login_attempts' => 0,
            'locked_until' => null
        ]);
        
        AuditService::logSecurity('ACCOUNT_UNLOCKED', [
            'user_id' => $user->id,
            'username' => $user->username,
            'timestamp' => now()->toISOString()
        ], $user);
    }
    
    /**
     * Send security alert
     */
    private static function sendSecurityAlert(string $event, array $details): void
    {
        // Log the alert
        \Log::warning('Security Alert: ' . $event, $details);
        
        // Send email alert if configured
        if (config('mail.admin_email')) {
            try {
                Mail::raw("Security Alert: {$event}\n\nDetails: " . json_encode($details, JSON_PRETTY_PRINT), function ($message) use ($event) {
                    $message->to(config('mail.admin_email'))
                           ->subject("Security Alert: {$event}")
                           ->from(config('mail.from.address'), config('mail.from.name'));
                });
            } catch (\Exception $e) {
                \Log::error('Failed to send security alert email: ' . $e->getMessage());
            }
        }
    }
    
    /**
     * Get security statistics
     */
    public static function getSecurityStats(): array
    {
        $last24Hours = now()->subDay();
        
        return [
            'failed_logins_24h' => AuditLog::where('action', 'LIKE', '%LOGIN_FAILED%')
                ->where('created_at', '>=', $last24Hours)
                ->count(),
            'suspicious_activities_24h' => AuditLog::where('action', 'LIKE', '%SUSPICIOUS%')
                ->where('created_at', '>=', $last24Hours)
                ->count(),
            'locked_accounts' => User::where('locked_until', '>', now())->count(),
            'security_score' => self::calculateSecurityScore()
        ];
    }
    
    /**
     * Calculate security score
     */
    private static function calculateSecurityScore(): int
    {
        $score = 100;
        
        // Deduct points for failed logins
        $failedLogins = AuditLog::where('action', 'LIKE', '%LOGIN_FAILED%')
            ->where('created_at', '>=', now()->subDay())
            ->count();
        
        $score -= min(30, $failedLogins * 2);
        
        // Deduct points for suspicious activities
        $suspiciousActivities = AuditLog::where('action', 'LIKE', '%SUSPICIOUS%')
            ->where('created_at', '>=', now()->subDay())
            ->count();
        
        $score -= min(20, $suspiciousActivities * 5);
        
        // Deduct points for locked accounts
        $lockedAccounts = User::where('locked_until', '>', now())->count();
        $score -= min(10, $lockedAccounts * 2);
        
        return max(0, $score);
    }
}

