<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuditService
{
    /**
     * Log user activity
     */
    public static function log(string $action, array $details = [], ?User $user = null, ?Request $request = null): void
    {
        $user = $user ?? Auth::user();
        $request = $request ?? request();
        
        if (!$user) {
            return; // Skip logging if no user
        }

        $ipAddress = $request ? $request->ip() : null;
        
        // Get additional request details
        $requestDetails = [];
        if ($request) {
            $requestDetails = [
                'user_agent' => $request->userAgent(),
                'url' => $request->fullUrl(),
                'method' => $request->method(),
                'route' => $request->route() ? $request->route()->getName() : null,
            ];
        }

        // Merge details with request information
        $allDetails = array_merge($details, $requestDetails);

        AuditLog::create([
            'user_id' => $user->id,
            'action' => $action,
            'details' => $allDetails,
            'ip_address' => $ipAddress,
        ]);
    }

    /**
     * Log authentication events
     */
    public static function logAuth(string $action, ?User $user = null, ?Request $request = null, array $additionalDetails = []): void
    {
        $details = array_merge([
            'event_type' => 'authentication',
            'timestamp' => now()->toISOString(),
        ], $additionalDetails);

        self::log($action, $details, $user, $request);
    }

    /**
     * Log CRUD operations
     */
    public static function logCrud(string $operation, string $model, $modelId = null, array $changes = [], ?User $user = null, ?Request $request = null): void
    {
        $details = [
            'event_type' => 'crud_operation',
            'model' => $model,
            'model_id' => $modelId,
            'operation' => $operation,
            'changes' => $changes,
            'timestamp' => now()->toISOString(),
        ];

        $action = ucfirst($operation) . ' ' . $model;
        if ($modelId) {
            $action .= " (ID: {$modelId})";
        }

        self::log($action, $details, $user, $request);
    }

    /**
     * Log data import/export
     */
    public static function logDataTransfer(string $operation, string $dataType, int $recordCount = 0, array $additionalDetails = [], ?User $user = null, ?Request $request = null): void
    {
        $details = [
            'event_type' => 'data_transfer',
            'data_type' => $dataType,
            'record_count' => $recordCount,
            'operation' => $operation,
            'timestamp' => now()->toISOString(),
        ];

        $details = array_merge($details, $additionalDetails);

        $action = ucfirst($operation) . ' ' . $dataType;
        if ($recordCount > 0) {
            $action .= " ({$recordCount} records)";
        }

        self::log($action, $details, $user, $request);
    }

    /**
     * Log system events
     */
    public static function logSystem(string $event, array $details = [], ?User $user = null, ?Request $request = null): void
    {
        $systemDetails = [
            'event_type' => 'system_event',
            'timestamp' => now()->toISOString(),
        ];

        $allDetails = array_merge($systemDetails, $details);

        self::log($event, $allDetails, $user, $request);
    }

    /**
     * Log security events
     */
    public static function logSecurity(string $event, array $details = [], ?User $user = null, ?Request $request = null): void
    {
        $securityDetails = [
            'event_type' => 'security_event',
            'severity' => $details['severity'] ?? 'medium',
            'timestamp' => now()->toISOString(),
        ];

        $allDetails = array_merge($securityDetails, $details);

        self::log($event, $allDetails, $user, $request);
    }

    /**
     * Get audit logs with filters
     */
    public static function getLogs(array $filters = [], int $limit = 50, int $offset = 0)
    {
        $query = AuditLog::with('user');

        // Filter by user
        if (isset($filters['user_id'])) {
            $query->where('user_id', $filters['user_id']);
        }

        // Filter by action
        if (isset($filters['action'])) {
            $query->where('action', 'like', '%' . $filters['action'] . '%');
        }

        // Filter by event type
        if (isset($filters['event_type'])) {
            $query->whereJsonContains('details->event_type', $filters['event_type']);
        }

        // Filter by date range
        if (isset($filters['date_from'])) {
            $query->where('created_at', '>=', $filters['date_from']);
        }

        if (isset($filters['date_to'])) {
            $query->where('created_at', '<=', $filters['date_to']);
        }

        // Filter by IP address
        if (isset($filters['ip_address'])) {
            $query->where('ip_address', $filters['ip_address']);
        }

        return $query->orderBy('created_at', 'desc')
                    ->limit($limit)
                    ->offset($offset)
                    ->get();
    }

    /**
     * Get audit statistics
     */
    public static function getStatistics(array $filters = [])
    {
        $query = AuditLog::query();

        // Apply same filters as getLogs
        if (isset($filters['user_id'])) {
            $query->where('user_id', $filters['user_id']);
        }

        if (isset($filters['date_from'])) {
            $query->where('created_at', '>=', $filters['date_from']);
        }

        if (isset($filters['date_to'])) {
            $query->where('created_at', '<=', $filters['date_to']);
        }

        $totalLogs = $query->count();

        // Get logs by event type
        $logsByEventType = $query->get()
            ->groupBy(function ($log) {
                return $log->details['event_type'] ?? 'unknown';
            })
            ->map(function ($logs) {
                return $logs->count();
            });

        // Get logs by action
        $logsByAction = $query->get()
            ->groupBy('action')
            ->map(function ($logs) {
                return $logs->count();
            })
            ->sortDesc()
            ->take(10);

        // Get unique users
        $uniqueUsers = $query->distinct('user_id')->count('user_id');

        // Get unique IPs
        $uniqueIPs = $query->distinct('ip_address')->count('ip_address');

        return [
            'total_logs' => $totalLogs,
            'logs_by_event_type' => $logsByEventType,
            'top_actions' => $logsByAction,
            'unique_users' => $uniqueUsers,
            'unique_ips' => $uniqueIPs,
        ];
    }
}
