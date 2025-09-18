<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\FaceAttendance;
use App\Services\AuditService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class FaceAttendanceController extends Controller
{
    protected $auditService;

    public function __construct(AuditService $auditService)
    {
        $this->auditService = $auditService;
    }

    /**
     * Process face recognition attendance
     */
    public function processFaceAttendance(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'employee_id' => 'required|integer|exists:employees,id',
                'timestamp' => 'required|date',
                'photo' => 'required|string', // Base64 encoded image
                'confidence' => 'nullable|numeric|min:0|max:1',
                'location' => 'nullable|string|max:255',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Data tidak valid',
                    'errors' => $validator->errors()
                ], 422);
            }

            $employee = Employee::find($request->employee_id);
            if (!$employee) {
                return response()->json([
                    'success' => false,
                    'message' => 'Pegawai tidak ditemukan'
                ], 404);
            }

            // Check if already attended today
            $todayAttendance = FaceAttendance::where('employee_id', $employee->id)
                ->whereDate('attendance_date', Carbon::today())
                ->first();

            if ($todayAttendance) {
                return response()->json([
                    'success' => false,
                    'message' => 'Anda sudah melakukan absensi hari ini',
                    'data' => [
                        'attendance_time' => $todayAttendance->attendance_time,
                        'attendance_type' => $todayAttendance->attendance_type
                    ]
                ], 400);
            }

            // Save photo
            $photoPath = $this->saveAttendancePhoto($request->photo, $employee->id);

            // Create attendance record
            $attendance = FaceAttendance::create([
                'employee_id' => $employee->id,
                'attendance_date' => Carbon::parse($request->timestamp)->toDateString(),
                'attendance_time' => Carbon::parse($request->timestamp)->toTimeString(),
                'attendance_type' => $this->determineAttendanceType($request->timestamp),
                'photo_path' => $photoPath,
                'confidence_score' => $request->confidence ?? 0.95,
                'location' => $request->location,
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'status' => 'present'
            ]);

            // Log audit
            $this->auditService->log([
                'action' => 'face_attendance_created',
                'description' => "Face attendance recorded for {$employee->name}",
                'user_id' => auth()->id(),
                'model_type' => 'FaceAttendance',
                'model_id' => $attendance->id,
                'old_values' => null,
                'new_values' => $attendance->toArray()
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Absensi berhasil dicatat',
                'data' => [
                    'attendance' => $attendance,
                    'employee' => $employee,
                    'attendance_time' => $attendance->attendance_time,
                    'attendance_type' => $attendance->attendance_type
                ]
            ]);

        } catch (\Exception $e) {
            \Log::error('Face attendance processing error: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan sistem',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get face attendance history
     */
    public function getFaceAttendanceHistory(Request $request)
    {
        try {
            $query = FaceAttendance::with('employee')
                ->orderBy('attendance_date', 'desc')
                ->orderBy('attendance_time', 'desc');

            // Filter by date range
            if ($request->has('start_date')) {
                $query->where('attendance_date', '>=', $request->start_date);
            }
            if ($request->has('end_date')) {
                $query->where('attendance_date', '<=', $request->end_date);
            }

            // Filter by employee
            if ($request->has('employee_id')) {
                $query->where('employee_id', $request->employee_id);
            }

            // Filter by attendance type
            if ($request->has('attendance_type')) {
                $query->where('attendance_type', $request->attendance_type);
            }

            $perPage = $request->get('per_page', 15);
            $attendances = $query->paginate($perPage);

            return response()->json([
                'success' => true,
                'data' => $attendances
            ]);

        } catch (\Exception $e) {
            \Log::error('Face attendance history error: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil riwayat absensi'
            ], 500);
        }
    }

    /**
     * Register employee face for recognition
     */
    public function registerFace(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'employee_id' => 'required|integer|exists:employees,id',
                'face_photo' => 'required|string', // Base64 encoded image
                'face_descriptor' => 'nullable|string', // Face recognition descriptor
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Data tidak valid',
                    'errors' => $validator->errors()
                ], 422);
            }

            $employee = Employee::find($request->employee_id);
            if (!$employee) {
                return response()->json([
                    'success' => false,
                    'message' => 'Pegawai tidak ditemukan'
                ], 404);
            }

            // Save face photo
            $facePhotoPath = $this->saveFacePhoto($request->face_photo, $employee->id);

            // Update employee with face data
            $employee->update([
                'face_photo_path' => $facePhotoPath,
                'face_descriptor' => $request->face_descriptor,
                'face_registered_at' => now()
            ]);

            // Log audit
            $this->auditService->log([
                'action' => 'face_registered',
                'description' => "Face registered for {$employee->name}",
                'user_id' => auth()->id(),
                'model_type' => 'Employee',
                'model_id' => $employee->id,
                'old_values' => null,
                'new_values' => [
                    'face_photo_path' => $facePhotoPath,
                    'face_registered_at' => now()
                ]
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Wajah berhasil didaftarkan',
                'data' => [
                    'employee' => $employee,
                    'face_photo_path' => $facePhotoPath
                ]
            ]);

        } catch (\Exception $e) {
            \Log::error('Face registration error: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Gagal mendaftarkan wajah',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get face attendance statistics
     */
    public function getStatistics(Request $request)
    {
        try {
            $startDate = $request->get('start_date', Carbon::now()->startOfMonth());
            $endDate = $request->get('end_date', Carbon::now()->endOfMonth());

            $stats = [
                'total_attendances' => FaceAttendance::whereBetween('attendance_date', [$startDate, $endDate])->count(),
                'present_count' => FaceAttendance::whereBetween('attendance_date', [$startDate, $endDate])
                    ->where('status', 'present')->count(),
                'late_count' => FaceAttendance::whereBetween('attendance_date', [$startDate, $endDate])
                    ->where('attendance_type', 'late')->count(),
                'average_confidence' => FaceAttendance::whereBetween('attendance_date', [$startDate, $endDate])
                    ->avg('confidence_score'),
                'registered_faces' => Employee::whereNotNull('face_photo_path')->count(),
                'total_employees' => Employee::count(),
                'attendance_by_day' => FaceAttendance::whereBetween('attendance_date', [$startDate, $endDate])
                    ->selectRaw('attendance_date, COUNT(*) as count')
                    ->groupBy('attendance_date')
                    ->orderBy('attendance_date')
                    ->get(),
                'attendance_by_type' => FaceAttendance::whereBetween('attendance_date', [$startDate, $endDate])
                    ->selectRaw('attendance_type, COUNT(*) as count')
                    ->groupBy('attendance_type')
                    ->get()
            ];

            return response()->json([
                'success' => true,
                'data' => $stats
            ]);

        } catch (\Exception $e) {
            \Log::error('Face attendance statistics error: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil statistik'
            ], 500);
        }
    }

    /**
     * Save attendance photo
     */
    private function saveAttendancePhoto($base64Photo, $employeeId)
    {
        $imageData = base64_decode(preg_replace('#^data:image/\w+;base64,#i', '', $base64Photo));
        $fileName = 'attendance_' . $employeeId . '_' . time() . '.jpg';
        $path = 'attendance_photos/' . $fileName;
        
        Storage::disk('public')->put($path, $imageData);
        
        return $path;
    }

    /**
     * Save face photo for registration
     */
    private function saveFacePhoto($base64Photo, $employeeId)
    {
        $imageData = base64_decode(preg_replace('#^data:image/\w+;base64,#i', '', $base64Photo));
        $fileName = 'face_' . $employeeId . '_' . time() . '.jpg';
        $path = 'face_photos/' . $fileName;
        
        Storage::disk('public')->put($path, $imageData);
        
        return $path;
    }

    /**
     * Determine attendance type based on time
     */
    private function determineAttendanceType($timestamp)
    {
        $time = Carbon::parse($timestamp)->format('H:i:s');
        
        // Define work schedule (adjust as needed)
        $workStart = '07:00:00';
        $workEnd = '15:00:00';
        
        if ($time <= $workStart) {
            return 'early';
        } elseif ($time <= '08:00:00') {
            return 'on_time';
        } elseif ($time <= $workEnd) {
            return 'late';
        } else {
            return 'overtime';
        }
    }
}
