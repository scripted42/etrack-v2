<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Student;
use App\Models\Employee;
use App\Models\AuditLog;
use App\Services\DataQualityService;
use App\Services\SecurityMonitoringService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class DashboardController extends Controller
{
    /**
     * Get dashboard statistics
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        if (!$user->role->permissions->contains('name', 'view_dashboard')) {
            return response()->json([
                'success' => false,
                'message' => 'Tidak memiliki akses untuk melihat dashboard'
            ], 403);
        }

        try {
            // ==========================================
            // KPI UTAMA UNTUK KEPALA SEKOLAH (ISO 9001)
            // ==========================================
            
            // 1. KAPASITAS SEKOLAH & UTILISASI
            $totalStudents = Student::count();
            $totalEmployees = Employee::count();
            $activeStudents = Student::where('status', 'aktif')->count();
            $activeEmployees = Employee::where('status', 'aktif')->count();
            $totalUsers = User::count();
            $activeUsers = User::where('status', 'aktif')->count();
            
            // Kapasitas maksimal sekolah (asumsi 500 siswa)
            $maxCapacity = 500;
            $utilizationRate = round(($totalStudents / $maxCapacity) * 100, 1);
            
            // 2. DISTRIBUSI SISWA PER KELAS & TINGKAT
            $studentsByClass = Student::select('kelas', DB::raw('count(*) as count'))
                ->whereNotNull('kelas')
                ->groupBy('kelas')
                ->orderBy('kelas')
                ->get()
                ->pluck('count', 'kelas');
            
            // Analisis tingkat (X, XI, XII)
            $studentsByLevel = $this->analyzeStudentsByLevel($studentsByClass);
            
            // 3. KUALITAS DATA & KELENGKAPAN
            $dataQuality = $this->analyzeDataQuality();
            
            // 4. TREND PERTUMBUHAN SISWA (6 BULAN TERAKHIR)
            $growthTrend = $this->getGrowthTrend();
            
            // 5. STATUS KEPEGAWAIAN & RASIO GURU-SISWA
            $employeeAnalysis = $this->analyzeEmployeeStatus();
            $teacherStudentRatio = $this->calculateTeacherStudentRatio();
            
            // 6. AKTIVITAS SISTEM & KEAMANAN
            $systemActivity = $this->analyzeSystemActivity();
            
            // 7. ALERT & NOTIFIKASI PENTING
            $alerts = $this->generateAlerts($totalStudents, $utilizationRate, $dataQuality);
            
            // 8. PERFORMANCE INDICATORS
            $performanceIndicators = $this->getPerformanceIndicators();

            return response()->json([
                'success' => true,
                'data' => [
                    // KPI UTAMA
                    'kpi' => [
                        'total_students' => $totalStudents,
                        'total_employees' => $totalEmployees,
                        'active_students' => $activeStudents,
                        'active_employees' => $activeEmployees,
                        'utilization_rate' => $utilizationRate,
                        'max_capacity' => $maxCapacity,
                        'teacher_student_ratio' => $teacherStudentRatio
                    ],
                    
                    // DISTRIBUSI & ANALISIS
                    'distribution' => [
                        'students_by_class' => $studentsByClass,
                        'students_by_level' => $studentsByLevel,
                        'employee_analysis' => $employeeAnalysis
                    ],
                    
                    // KUALITAS & PERFORMANCE
                    'quality' => [
                        'data_quality' => $dataQuality,
                        'performance_indicators' => $performanceIndicators
                    ],
                    
                    // TREND & GROWTH
                    'trends' => [
                        'growth_trend' => $growthTrend,
                        'monthly_stats' => $this->getMonthlyStatistics()
                    ],
                    
                    // STUDENT GROWTH DATA FOR CHARTS
                    'student_growth' => $this->getStudentGrowthData(),
                    
                    // SISTEM & KEAMANAN
                    'system' => [
                        'activity' => $systemActivity,
                        'alerts' => $alerts
                    ],
                    
                    // LEGACY DATA (untuk kompatibilitas)
                    'overview' => [
                        'total_students' => $totalStudents,
                        'total_employees' => $totalEmployees,
                        'total_users' => $totalUsers,
                        'active_users' => $activeUsers
                    ]
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal memuat data dashboard',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get monthly statistics for charts
     */
    private function getMonthlyStatistics(): array
    {
        $months = [];
        $studentData = [];
        $employeeData = [];

        // Get last 12 months
        for ($i = 11; $i >= 0; $i--) {
            $date = Carbon::now()->subMonths($i);
            $monthName = $date->format('M Y');
            $months[] = $monthName;

            // Count students created in this month
            $studentCount = Student::whereYear('created_at', $date->year)
                ->whereMonth('created_at', $date->month)
                ->count();
            $studentData[] = $studentCount;

            // Count employees created in this month
            $employeeCount = Employee::whereYear('created_at', $date->year)
                ->whereMonth('created_at', $date->month)
                ->count();
            $employeeData[] = $employeeCount;
        }

        return [
            'months' => $months,
            'students' => $studentData,
            'employees' => $employeeData
        ];
    }

    /**
     * Get growth statistics
     */
    private function getGrowthStatistics(): array
    {
        $currentYear = Carbon::now()->year;
        $lastYear = $currentYear - 1;

        // Current year stats
        $currentYearStudents = Student::whereYear('created_at', $currentYear)->count();
        $currentYearEmployees = Employee::whereYear('created_at', $currentYear)->count();

        // Last year stats
        $lastYearStudents = Student::whereYear('created_at', $lastYear)->count();
        $lastYearEmployees = Employee::whereYear('created_at', $lastYear)->count();

        // Calculate growth percentages
        $studentGrowth = $lastYearStudents > 0 
            ? round((($currentYearStudents - $lastYearStudents) / $lastYearStudents) * 100, 2)
            : 0;

        $employeeGrowth = $lastYearEmployees > 0 
            ? round((($currentYearEmployees - $lastYearEmployees) / $lastYearEmployees) * 100, 2)
            : 0;

        return [
            'students' => [
                'current_year' => $currentYearStudents,
                'last_year' => $lastYearStudents,
                'growth_percentage' => $studentGrowth
            ],
            'employees' => [
                'current_year' => $currentYearEmployees,
                'last_year' => $lastYearEmployees,
                'growth_percentage' => $employeeGrowth
            ]
        ];
    }

    /**
     * Get system health status
     */
    public function health(Request $request): JsonResponse
    {
        $user = $request->user();

        if (!$user->role->permissions->contains('name', 'view_dashboard')) {
            return response()->json([
                'success' => false,
                'message' => 'Tidak memiliki akses untuk melihat dashboard'
            ], 403);
        }

        try {
            // Database connection test
            $dbStatus = 'healthy';
            try {
                DB::connection()->getPdo();
            } catch (\Exception $e) {
                $dbStatus = 'unhealthy';
            }

            // Storage space check
            $storagePath = storage_path();
            $totalSpace = disk_total_space($storagePath);
            $freeSpace = disk_free_space($storagePath);
            $usedSpace = $totalSpace - $freeSpace;
            $storageUsage = round(($usedSpace / $totalSpace) * 100, 2);

            // Recent error logs
            $errorCount = 0;
            $logFile = storage_path('logs/laravel.log');
            if (file_exists($logFile)) {
                $logContent = file_get_contents($logFile);
                $errorCount = substr_count($logContent, 'ERROR');
            }

        return response()->json([
            'success' => true,
                'data' => [
                    'database' => $dbStatus,
                    'storage' => [
                        'total_space' => $this->formatBytes($totalSpace),
                        'used_space' => $this->formatBytes($usedSpace),
                        'free_space' => $this->formatBytes($freeSpace),
                        'usage_percentage' => $storageUsage
                    ],
                    'errors' => $errorCount,
                    'last_updated' => Carbon::now()->format('Y-m-d H:i:s')
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal memuat status sistem',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get dashboard statistics (alias for index)
     */
    public function statistics(Request $request): JsonResponse
    {
        return $this->index($request);
    }

    /**
     * Get chart data
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

        try {
            $monthlyStats = $this->getMonthlyStatistics();
            $growthStats = $this->getGrowthStatistics();

        return response()->json([
            'success' => true,
                'data' => [
                    'monthly_stats' => $monthlyStats,
                    'growth_stats' => $growthStats
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal memuat data chart',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Analisis siswa per tingkat SMPN (VII, VIII, IX)
     */
    private function analyzeStudentsByLevel($studentsByClass): array
    {
        // SMPN hanya memiliki kelas 7, 8, dan 9
        $levels = ['VII' => 0, 'VIII' => 0, 'IX' => 0];
        
        foreach ($studentsByClass as $kelas => $count) {
            // Format kelas SMPN: 7A, 7B, 8D, 9B, dll
            if (preg_match('/^7[A-Z]/', $kelas)) {
                $levels['VII'] += $count;
            } elseif (preg_match('/^8[A-Z]/', $kelas)) {
                $levels['VIII'] += $count;
            } elseif (preg_match('/^9[A-Z]/', $kelas)) {
                $levels['IX'] += $count;
            }
        }
        
        return $levels;
    }
    
    /**
     * Analisis kualitas data
     */
    private function analyzeDataQuality(): array
    {
        $totalStudents = Student::count();
        $studentsWithCompleteData = Student::whereHas('identity', function($query) {
            $query->whereNotNull('nik')
                  ->whereNotNull('tempat_lahir')
                  ->whereNotNull('tanggal_lahir')
                  ->whereNotNull('jenis_kelamin');
        })->count();
        
        $completenessRate = $totalStudents > 0 ? round(($studentsWithCompleteData / $totalStudents) * 100, 1) : 0;
        
        return [
            'completeness_rate' => $completenessRate,
            'complete_records' => $studentsWithCompleteData,
            'incomplete_records' => $totalStudents - $studentsWithCompleteData,
            'status' => $completenessRate >= 90 ? 'excellent' : ($completenessRate >= 70 ? 'good' : 'needs_improvement')
        ];
    }
    
    /**
     * Trend pertumbuhan 6 bulan terakhir
     */
    private function getGrowthTrend(): array
    {
        $months = [];
        $studentCounts = [];
        
        for ($i = 5; $i >= 0; $i--) {
            $date = Carbon::now()->subMonths($i);
            $months[] = $date->format('M Y');
            
            $count = Student::whereYear('created_at', $date->year)
                ->whereMonth('created_at', $date->month)
                ->count();
            $studentCounts[] = $count;
        }
        
        return [
            'months' => $months,
            'student_counts' => $studentCounts,
            'trend_direction' => $this->calculateTrendDirection($studentCounts)
        ];
    }
    
    /**
     * Analisis status kepegawaian
     */
    private function analyzeEmployeeStatus(): array
    {
        $totalEmployees = Employee::count();
        $activeEmployees = Employee::where('status', 'aktif')->count();
        
        // Kategorisasi yang lebih detail untuk SMPN
        $teachers = Employee::where('jabatan', 'like', '%Guru%')->where('status', 'aktif')->count();
        $tataUsaha = Employee::where(function($query) {
            $query->where('jabatan', 'like', '%Tata Usaha%')
                  ->orWhere('jabatan', 'like', '%TU%')
                  ->orWhere('jabatan', 'like', '%Administrasi%');
        })->where('status', 'aktif')->count();
        $humas = Employee::where('jabatan', 'like', '%Humas%')->where('status', 'aktif')->count();
        $security = Employee::where(function($query) {
            $query->where('jabatan', 'like', '%Security%')
                  ->orWhere('jabatan', 'like', '%Satpam%')
                  ->orWhere('jabatan', 'like', '%Keamanan%');
        })->where('status', 'aktif')->count();
        $lainnya = Employee::where('jabatan', 'not like', '%Guru%')
                           ->where('jabatan', 'not like', '%Tata Usaha%')
                           ->where('jabatan', 'not like', '%TU%')
                           ->where('jabatan', 'not like', '%Administrasi%')
                           ->where('jabatan', 'not like', '%Humas%')
                           ->where('jabatan', 'not like', '%Security%')
                           ->where('jabatan', 'not like', '%Satpam%')
                           ->where('jabatan', 'not like', '%Keamanan%')
                           ->where('status', 'aktif')->count();
        
        $totalStaff = $tataUsaha + $humas + $security + $lainnya;
        
        return [
            'total' => $totalEmployees,
            'active' => $activeEmployees,
            'teachers' => $teachers,
            'staff' => $totalStaff,
            'staff_breakdown' => [
                'tata_usaha' => $tataUsaha,
                'humas' => $humas,
                'security' => $security,
                'lainnya' => $lainnya
            ],
            'active_percentage' => $totalEmployees > 0 ? round(($activeEmployees / $totalEmployees) * 100, 1) : 0
        ];
    }
    
    /**
     * Hitung rasio guru-siswa
     */
    private function calculateTeacherStudentRatio(): array
    {
        $totalStudents = Student::count();
        $totalTeachers = Employee::where('jabatan', 'like', '%Guru%')->where('status', 'aktif')->count();
        
        $ratio = $totalTeachers > 0 ? round($totalStudents / $totalTeachers, 1) : 0;
        
        return [
            'ratio' => $ratio,
            'students' => $totalStudents,
            'teachers' => $totalTeachers,
            'status' => $ratio <= 20 ? 'excellent' : ($ratio <= 30 ? 'good' : 'needs_improvement')
        ];
    }
    
    /**
     * Analisis aktivitas sistem
     */
    private function analyzeSystemActivity(): array
    {
        $last24Hours = AuditLog::where('created_at', '>=', Carbon::now()->subDay())->count();
        $last7Days = AuditLog::where('created_at', '>=', Carbon::now()->subDays(7))->count();
        $loginAttempts = AuditLog::where('action', 'LOGIN_SUCCESS')
            ->where('created_at', '>=', Carbon::now()->subDay())
            ->count();
        $failedLogins = AuditLog::where('action', 'LOGIN_FAILED')
            ->where('created_at', '>=', Carbon::now()->subDay())
            ->count();
        
        return [
            'activity_24h' => $last24Hours,
            'activity_7d' => $last7Days,
            'successful_logins' => $loginAttempts,
            'failed_logins' => $failedLogins,
            'security_score' => $this->calculateSecurityScore($loginAttempts, $failedLogins)
        ];
    }
    
    /**
     * Generate alerts untuk kepala sekolah
     */
    private function generateAlerts($totalStudents, $utilizationRate, $dataQuality): array
    {
        $alerts = [];
        
        // Alert kapasitas
        if ($utilizationRate >= 90) {
            $alerts[] = [
                'type' => 'warning',
                'title' => 'Kapasitas Sekolah Hampir Penuh',
                'message' => "Utilisasi mencapai {$utilizationRate}%. Pertimbangkan penambahan ruang kelas.",
                'priority' => 'high'
            ];
        }
        
        // Alert kualitas data
        if ($dataQuality['completeness_rate'] < 70) {
            $alerts[] = [
                'type' => 'error',
                'title' => 'Kualitas Data Perlu Diperbaiki',
                'message' => "Kelengkapan data hanya {$dataQuality['completeness_rate']}%. Segera lengkapi data siswa.",
                'priority' => 'high'
            ];
        }
        
        // Alert pertumbuhan
        if ($totalStudents < 50) {
            $alerts[] = [
                'type' => 'info',
                'title' => 'Jumlah Siswa Rendah',
                'message' => "Total siswa hanya {$totalStudents}. Pertimbangkan strategi penerimaan siswa baru.",
                'priority' => 'medium'
            ];
        }
        
        return $alerts;
    }
    
    /**
     * Performance indicators
     */
    private function getPerformanceIndicators(): array
    {
        // Use DataQualityService for better data quality calculation
        $dataQuality = DataQualityService::calculateDataCompleteness();
        $securityStats = SecurityMonitoringService::getSecurityStats();
        
        // 1. Data Completeness Rate (improved calculation)
        $dataCompleteness = $dataQuality['completeness_percentage'];
        
        // 2. System Activity Rate (berdasarkan login activity)
        $totalUsers = User::count();
        $activeUsers = User::where('last_login', '>=', now()->subDays(7))->count();
        $activityRate = $totalUsers > 0 ? round(($activeUsers / $totalUsers) * 100, 1) : 0;
        
        // 3. Data Utilization Rate (berapa banyak data yang digunakan)
        $totalCapacity = 500; // Kapasitas maksimal sekolah
        $currentUtilization = $dataQuality['total_students'];
        $utilizationRate = round(($currentUtilization / $totalCapacity) * 100, 1);
        
        // 4. System Health Score (berdasarkan status pegawai dan kelengkapan data)
        $totalEmployees = Employee::count();
        $activeEmployees = Employee::where('status', 'aktif')->count();
        $employeeHealth = $totalEmployees > 0 ? round(($activeEmployees / $totalEmployees) * 100, 1) : 0;
        $systemHealth = round(($dataCompleteness + $employeeHealth) / 2, 1);
        
        // 5. Security Score (from SecurityMonitoringService)
        $securityScore = $securityStats['security_score'];
        
        return [
            'data_completeness' => $dataCompleteness,
            'activity_rate' => $activityRate,
            'utilization_rate' => $utilizationRate,
            'system_health' => $systemHealth,
            'security_score' => $securityScore,
            'quality_score' => DataQualityService::calculateQualityScore()
        ];
    }
    
    /**
     * Hitung arah trend
     */
    private function calculateTrendDirection($counts): string
    {
        if (count($counts) < 2) return 'stable';
        
        $recent = array_slice($counts, -3);
        $older = array_slice($counts, 0, 3);
        
        $recentAvg = array_sum($recent) / count($recent);
        $olderAvg = array_sum($older) / count($older);
        
        if ($recentAvg > $olderAvg * 1.1) return 'increasing';
        if ($recentAvg < $olderAvg * 0.9) return 'decreasing';
        return 'stable';
    }
    
    /**
     * Hitung security score
     */
    private function calculateSecurityScore($successful, $failed): int
    {
        if ($successful == 0) return 0;
        
        $ratio = $failed / $successful;
        if ($ratio <= 0.1) return 100;
        if ($ratio <= 0.2) return 80;
        if ($ratio <= 0.5) return 60;
        return 40;
    }

    /**
     * Get student growth data for charts
     */
    private function getStudentGrowthData(): array
    {
        $currentYear = Carbon::now()->year;
        $years = [];
        $totalStudents = [];
        $newStudents = [];
        
        // Get data for last 6 years
        for ($i = 5; $i >= 0; $i--) {
            $year = $currentYear - $i;
            $years[] = $year;
            
            // Count total students created up to this year
            $totalCount = Student::whereYear('created_at', '<=', $year)->count();
            $totalStudents[] = $totalCount;
            
            // Count new students in this year
            $newCount = Student::whereYear('created_at', $year)->count();
            $newStudents[] = $newCount;
        }
        
        return [
            'years' => $years,
            'total_students' => $totalStudents,
            'new_students' => $newStudents
        ];
    }

    /**
     * Format bytes to human readable format
     */
    private function formatBytes($bytes, $precision = 2): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        
        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }
        
        return round($bytes, $precision) . ' ' . $units[$i];
    }
}