<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\AuditService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class AuditLogController extends Controller
{
    /**
     * Display a listing of audit logs
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        // Check permission
        if (!$user->role->permissions->contains('name', 'view_audit_logs')) {
            return response()->json([
                'success' => false,
                'message' => 'Tidak memiliki akses untuk melihat audit logs'
            ], 403);
        }

        try {
            // Get filters from request
            $filters = $request->only([
                'user_id', 'action', 'event_type', 'date_from', 'date_to', 'ip_address'
            ]);

            // Get pagination parameters
            $limit = min($request->get('limit', 50), 100); // Max 100 records
            $offset = $request->get('offset', 0);

            // Get audit logs
            $logs = AuditService::getLogs($filters, $limit, $offset);

            // Get statistics
            $statistics = AuditService::getStatistics($filters);

            return response()->json([
                'success' => true,
                'data' => [
                    'logs' => $logs,
                    'statistics' => $statistics,
                    'pagination' => [
                        'limit' => $limit,
                        'offset' => $offset,
                        'has_more' => $logs->count() === $limit
                    ]
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal memuat audit logs',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified audit log
     */
    public function show(Request $request, int $id): JsonResponse
    {
        $user = $request->user();

        // Check permission
        if (!$user->role->permissions->contains('name', 'view_audit_logs')) {
            return response()->json([
                'success' => false,
                'message' => 'Tidak memiliki akses untuk melihat audit logs'
            ], 403);
        }

        try {
            $log = \App\Models\AuditLog::with('user')->findOrFail($id);

            return response()->json([
                'success' => true,
                'data' => $log
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Audit log tidak ditemukan',
                'error' => $e->getMessage()
            ], 404);
        }
    }

    /**
     * Get audit statistics
     */
    public function statistics(Request $request): JsonResponse
    {
        $user = $request->user();

        // Check permission
        if (!$user->role->permissions->contains('name', 'view_audit_logs')) {
            return response()->json([
                'success' => false,
                'message' => 'Tidak memiliki akses untuk melihat audit logs'
            ], 403);
        }

        try {
            $filters = $request->only([
                'user_id', 'date_from', 'date_to'
            ]);

            $statistics = AuditService::getStatistics($filters);

            return response()->json([
                'success' => true,
                'data' => $statistics
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal memuat statistik audit',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Export audit logs
     */
    public function export(Request $request): JsonResponse
    {
        $user = $request->user();

        // Check permission
        if (!$user->role->permissions->contains('name', 'export_audit_logs')) {
            return response()->json([
                'success' => false,
                'message' => 'Tidak memiliki akses untuk export audit logs'
            ], 403);
        }

        try {
            $filters = $request->only([
                'user_id', 'action', 'event_type', 'date_from', 'date_to', 'ip_address'
            ]);

            // Get all logs (no limit for export)
            $logs = AuditService::getLogs($filters, 10000, 0);

            // Log the export action
            AuditService::logDataTransfer('export', 'audit_logs', $logs->count(), [
                'export_format' => 'json',
                'filters_applied' => $filters
            ]);

            return response()->json([
                'success' => true,
                'data' => [
                    'logs' => $logs,
                    'export_info' => [
                        'exported_at' => now()->toISOString(),
                        'total_records' => $logs->count(),
                        'filters' => $filters
                    ]
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal export audit logs',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
