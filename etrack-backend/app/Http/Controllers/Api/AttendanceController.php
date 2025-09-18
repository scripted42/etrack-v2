<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\AttendanceVerification;
use App\Models\QrCodeSession;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class AttendanceController extends Controller
{
    /**
     * Get QR code for attendance scanning
     */
    public function getQrCode(): JsonResponse
    {
        try {
            // Generate new session
            $session = QrCodeSession::generateNewSession([
                'timestamp' => time(),
                'type' => 'attendance'
            ]);
            
            // Generate QR code data
            $qrData = json_encode([
                'token' => $session->session_token,
                'timestamp' => time(),
                'type' => 'attendance'
            ]);
            
            // Generate QR code image as base64 (using SVG format to avoid ImageMagick dependency)
            $qrCodeImage = QrCode::format('svg')
                ->size(200)
                ->margin(2)
                ->generate($qrData);
            
            // Convert to base64 data URL
            $qrCodeBase64 = 'data:image/svg+xml;base64,' . base64_encode($qrCodeImage);
            
            return response()->json([
                'success' => true,
                'data' => [
                    'token' => $session->session_token,
                    'data' => base64_encode($qrData),
                    'qrCodeImage' => $qrCodeBase64,
                    'expires_in' => $session->expires_at->diffInSeconds(now()),
                    'is_valid' => $session->isValid()
                ]
            ]);
            
        } catch (\Exception $e) {
            Log::error('Failed to generate QR code', [
                'error' => $e->getMessage()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Gagal generate QR code',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Process attendance with 3 key verification
     */
    public function processAttendance(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'qr_code' => 'required|string',
            'gps_location' => 'nullable|array',
            'gps_location.lat' => 'nullable|numeric',
            'gps_location.lng' => 'nullable|numeric',
            'gps_location.accuracy' => 'nullable|numeric',
            'gps_location.address' => 'nullable|string',
            'selfie_photo' => 'required|string', // base64 encoded image
            'attendance_type' => 'required|in:check_in,check_out',
            'notes' => 'nullable|string'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Data tidak valid',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $user = $request->user();
            
            // Verify QR code
            $qrSession = QrCodeSession::where('session_token', $request->qr_code)
                                    ->where('is_active', true)
                                    ->where('expires_at', '>', now())
                                    ->first();

            if (!$qrSession) {
                return response()->json([
                    'success' => false,
                    'message' => 'QR code tidak valid atau sudah expired'
                ], 400);
            }

            // Verify GPS location (within school radius) - only if GPS data is provided
            if ($request->gps_location && isset($request->gps_location['lat']) && isset($request->gps_location['lng'])) {
                $schoolLocation = $this->getSchoolLocation();
                $distance = $this->calculateDistance(
                    $request->gps_location['lat'],
                    $request->gps_location['lng'],
                    $schoolLocation['lat'],
                    $schoolLocation['lng']
                );

                if ($distance > $schoolLocation['radius']) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Lokasi tidak valid. Pastikan Anda berada di area sekolah.'
                    ], 400);
                }
            }

            // Save selfie photo
            $selfiePath = $this->saveSelfiePhoto($request->selfie_photo, $user->id);

            // Create or update attendance record
            $attendance = $this->createOrUpdateAttendance($user, $request->attendance_type, $request->notes);
            
            // Create verification record
            $verification = AttendanceVerification::create([
                'attendance_id' => $attendance->id,
                'gps_location' => $request->gps_location ? json_encode($request->gps_location) : null,
                'selfie_photo_path' => $selfiePath,
                'qr_code_scanned' => $request->qr_code,
                'device_info' => $request->header('User-Agent'),
                'ip_address' => $request->ip(),
                'verified_at' => now()
            ]);

            // Mark QR code as used
            $qrSession->update(['is_active' => false]);

            // Log attendance
            Log::info('Attendance processed successfully', [
                'user_id' => $user->id,
                'attendance_id' => $attendance->id,
                'type' => $request->attendance_type
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Attendance recorded successfully',
                'data' => [
                    'attendance' => $attendance,
                    'verification' => $verification
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Attendance processing failed', [
                'error' => $e->getMessage(),
                'user_id' => $request->user()->id ?? null
            ]);

            // Check if it's a validation error (attendance already exists)
            if (strpos($e->getMessage(), 'sudah melakukan absensi') !== false) {
                return response()->json([
                    'success' => false,
                    'message' => $e->getMessage()
                ], 400);
            }

            return response()->json([
                'success' => false,
                'message' => 'Gagal memproses absensi: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get attendance history for user
     */
    public function getAttendanceHistory(Request $request): JsonResponse
    {
        try {
            $user = $request->user();
            $month = $request->get('month', now()->month);
            $year = $request->get('year', now()->year);

            $attendances = Attendance::where('user_id', $user->id)
                ->whereYear('attendance_date', $year)
                ->whereMonth('attendance_date', $month)
                ->with('verification')
                ->orderBy('attendance_date', 'desc')
                ->get()
                ->map(function ($attendance) {
                    return [
                        'id' => $attendance->id,
                        'attendance_date' => $attendance->attendance_date->format('Y-m-d'),
                        'check_in_time' => $attendance->check_in_time ? $attendance->check_in_time->format('H:i:s') : null,
                        'check_out_time' => $attendance->check_out_time ? $attendance->check_out_time->format('H:i:s') : null,
                        'status' => $attendance->status,
                        'notes' => $attendance->notes,
                        'verification' => $attendance->verification
                    ];
                });

            $statistics = $this->calculateAttendanceStatistics($user->id, $year, $month);

            return response()->json([
                'success' => true,
                'data' => [
                    'attendances' => $attendances,
                    'statistics' => $statistics
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil data absensi',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get attendance dashboard data
     */
    public function getAttendanceDashboard(): JsonResponse
    {
        try {
            $today = now()->toDateString();
            
            $todayAttendance = Attendance::whereDate('attendance_date', $today)
                ->with(['user', 'verification'])
                ->get();

            $statistics = [
                'total_checked_in' => $todayAttendance->where('check_in_time', '!=', null)->count(),
                'total_checked_out' => $todayAttendance->where('check_out_time', '!=', null)->count(),
                'present_count' => $todayAttendance->where('status', 'present')->count(),
                'late_count' => $todayAttendance->where('status', 'late')->count(),
                'absent_count' => $todayAttendance->where('status', 'absent')->count(),
            ];

            return response()->json([
                'success' => true,
                'data' => [
                    'today_attendance' => $todayAttendance,
                    'statistics' => $statistics
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil data dashboard absensi',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Private helper methods
     */
    private function getSchoolLocation(): array
    {
        // Get from settings or config
        return [
            'lat' => config('attendance.school_latitude', -7.2374924571658195),
            'lng' => config('attendance.school_longitude', 112.62761534309656),
            'radius' => config('attendance.school_radius_km', 0.5)
        ];
    }

    private function saveSelfiePhoto(string $base64Image, int $userId): string
    {
        $imageData = base64_decode(preg_replace('#^data:image/\w+;base64,#i', '', $base64Image));
        $filename = 'attendance_selfie_' . $userId . '_' . time() . '.jpg';
        $path = 'attendance/selfies/' . $filename;
        
        Storage::disk('public')->put($path, $imageData);
        
        return $path;
    }

    private function createOrUpdateAttendance(User $user, string $type, ?string $notes): Attendance
    {
        $today = now()->toDateString();
        
        $attendance = Attendance::where('user_id', $user->id)
            ->whereDate('attendance_date', $today)
            ->first();

        // Check if user already checked in/out today
        if ($type === 'check_in' && $attendance && $attendance->check_in_time) {
            throw new \Exception('Anda sudah melakukan absensi masuk hari ini. Silakan lakukan absensi pulang.');
        }
        
        if ($type === 'check_out' && $attendance && $attendance->check_out_time) {
            throw new \Exception('Anda sudah melakukan absensi pulang hari ini.');
        }
        
        if ($type === 'check_out' && (!$attendance || !$attendance->check_in_time)) {
            throw new \Exception('Anda belum melakukan absensi masuk hari ini. Silakan lakukan absensi masuk terlebih dahulu.');
        }

        if (!$attendance) {
            $attendance = Attendance::create([
                'user_id' => $user->id,
                'type' => $user->role && $user->role->name === 'student' ? 'student' : 'employee',
                'attendance_date' => $today,
                'status' => 'present'
            ]);
        }

        if ($type === 'check_in') {
            $attendance->update([
                'check_in_time' => now(),
                'status' => $this->determineAttendanceStatus(now(), $user)
            ]);
        } else {
            $attendance->update([
                'check_out_time' => now()
            ]);
        }

        if ($notes) {
            $attendance->update(['notes' => $notes]);
        }

        return $attendance->fresh();
    }

    private function determineAttendanceStatus($checkInTime, $user = null): string
    {
        $checkInTimeFormatted = $checkInTime->format('H:i');
        
        // Determine work start time based on user role
        $workStartTime = $this->getWorkStartTime($user);
        
        return $checkInTimeFormatted > $workStartTime ? 'late' : 'present';
    }
    
    private function getWorkStartTime($user = null): string
    {
        if (!$user) {
            return config('attendance.work_start_time', '07:00');
        }
        
        // Check user role for different start times
        if ($user->role && $user->role->name === 'teacher') {
            return config('attendance.teacher_start_time', '06:30');
        } elseif ($user->role && $user->role->name === 'staff') {
            return config('attendance.staff_start_time', '07:00');
        }
        
        // Default for other roles
        return config('attendance.work_start_time', '07:00');
    }
    
    /**
     * Get attendance schedule info for user
     */
    public function getAttendanceSchedule(Request $request): JsonResponse
    {
        try {
            $user = $request->user();
            $userRole = $user->role ? $user->role->name : 'default';
            
            $schedule = [
                'user_role' => $userRole,
                'work_start_time' => $this->getWorkStartTime($user),
                'work_end_time' => config('attendance.work_end_time', '14:30'),
                'late_threshold' => $this->getLateThreshold($user),
                'work_duration_hours' => $this->getWorkDuration($user)
            ];
            
            return response()->json([
                'success' => true,
                'data' => $schedule
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil jadwal absensi',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    private function getLateThreshold($user = null): string
    {
        if (!$user) {
            return date('H:i', strtotime(config('attendance.work_start_time', '07:00') . ' +1 minute'));
        }
        
        if ($user->role && $user->role->name === 'teacher') {
            return config('attendance.teacher_late_threshold', '06:31');
        } elseif ($user->role && $user->role->name === 'staff') {
            return config('attendance.staff_late_threshold', '07:01');
        }
        
        return date('H:i', strtotime(config('attendance.work_start_time', '07:00') . ' +1 minute'));
    }
    
    private function getWorkDuration($user = null): float
    {
        $startTime = $this->getWorkStartTime($user);
        $endTime = config('attendance.work_end_time', '14:30');
        
        return (strtotime($endTime) - strtotime($startTime)) / 3600;
    }

    private function calculateAttendanceStatistics(int $userId, int $year, int $month): array
    {
        $totalDays = now()->setYear($year)->setMonth($month)->daysInMonth;
        $workingDays = $totalDays; // Simplified - should exclude weekends/holidays
        
        $attendances = Attendance::where('user_id', $userId)
            ->whereYear('attendance_date', $year)
            ->whereMonth('attendance_date', $month)
            ->get();

        $presentDays = $attendances->where('status', 'present')->count();
        $lateDays = $attendances->where('status', 'late')->count();
        $absentDays = $workingDays - $attendances->count();

        return [
            'total_days' => $totalDays,
            'working_days' => $workingDays,
            'present_days' => $presentDays,
            'late_days' => $lateDays,
            'absent_days' => $absentDays,
            'attendance_percentage' => $workingDays > 0 ? round(($presentDays / $workingDays) * 100, 2) : 0
        ];
    }

    private function calculateDistance(float $lat1, float $lon1, float $lat2, float $lon2): float
    {
        $earthRadius = 6371; // Earth's radius in kilometers
        
        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);
        
        $a = sin($dLat/2) * sin($dLat/2) + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLon/2) * sin($dLon/2);
        $c = 2 * atan2(sqrt($a), sqrt(1-$a));
        
        return $earthRadius * $c;
    }
}
