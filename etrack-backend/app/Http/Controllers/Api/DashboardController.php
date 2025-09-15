<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Student;
use App\Models\Employee;
use App\Models\AuditLog;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    /**
     * Get dashboard statistics
     */
    public function statistics(Request $request): JsonResponse
    {
        $user = $request->user();

        // Check if user has permission to view dashboard
        if (!$user->role->permissions->contains('name', 'view_dashboard')) {
            return response()->json([
                'success' => false,
                'message' => 'Tidak memiliki akses untuk melihat dashboard'
            ], 403);
        }

        $stats = [
            'total_users' => User::where('status', 'aktif')->count(),
            'total_students' => Student::where('status', 'aktif')->count(),
            'total_employees' => Employee::where('status', 'aktif')->count(),
            'students_by_class' => Student::where('status', 'aktif')
                ->select('kelas', DB::raw('count(*) as total'))
                ->groupBy('kelas')
                ->get(),
            'employees_by_position' => Employee::where('status', 'aktif')
                ->select('jabatan', DB::raw('count(*) as total'))
                ->groupBy('jabatan')
                ->get(),
            'recent_activities' => AuditLog::with('user')
                ->latest()
                ->limit(10)
                ->get(),
            'login_stats' => [
                'today' => AuditLog::where('action', 'LOGIN_SUCCESS')
                    ->whereDate('created_at', today())
                    ->count(),
                'this_week' => AuditLog::where('action', 'LOGIN_SUCCESS')
                    ->whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()])
                    ->count(),
                'this_month' => AuditLog::where('action', 'LOGIN_SUCCESS')
                    ->whereMonth('created_at', now()->month)
                    ->count(),
            ]
        ];

        return response()->json([
            'success' => true,
            'data' => $stats
        ]);
    }

    /**
     * Get chart data for dashboard
     */
    public function chartData(Request $request): JsonResponse
    {
        $user = $request->user();

        if (!$user->role->permissions->contains('name', 'view_dashboard')) {
            return response()->json([
                'success' => false,
                'message' => 'Tidak memiliki akses untuk melihat dashboard'
            ], 403);
        }

        $chartData = [
            'students_growth' => Student::select(
                DB::raw('YEAR(created_at) as year'),
                DB::raw('MONTH(created_at) as month'),
                DB::raw('COUNT(*) as total')
            )
                ->where('status', 'aktif')
                ->groupBy('year', 'month')
                ->orderBy('year')
                ->orderBy('month')
                ->get(),
            'employees_by_status' => Employee::select('status', DB::raw('COUNT(*) as total'))
                ->groupBy('status')
                ->get(),
            'students_by_status' => Student::select('status', DB::raw('COUNT(*) as total'))
                ->groupBy('status')
                ->get(),
        ];

        return response()->json([
            'success' => true,
            'data' => $chartData
        ]);
    }
}
