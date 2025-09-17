<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Exports\StudentExport;
use App\Exports\EmployeeExport;
use App\Exports\AuditTrailExport;
use App\Models\Student;
use App\Models\Employee;
use App\Models\AuditLog;
use App\Services\AuditService;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\DB;

class ExportController extends Controller
{
    public function exportStudents(Request $request)
    {
        try {
            $userId = $request->user()->id;
            $format = $request->get('format', 'xlsx');
            $kelas = $request->get('kelas');
            $status = $request->get('status');

            // Build query with filters
            $query = Student::with(['user', 'identity', 'contact', 'guardians']);
            
            if ($kelas) {
                $query->where('kelas', $kelas);
            }
            
            if ($status) {
                $query->where('status', $status);
            }

            $students = $query->get();
            
            // Log export activity
            AuditService::log('EXPORT_STUDENTS', [
                'event_type' => 'data_export',
                'export_type' => 'students',
                'total_count' => $students->count(),
                'filters' => [
                    'kelas' => $kelas,
                    'status' => $status,
                    'format' => $format
                ],
                'timestamp' => now()->toISOString()
            ], $request->user());

            // Log export history
            $this->logExportHistory('students', 'data_siswa', $students->count(), $userId, $format);

            $export = new StudentExport($students);
            
            if ($format === 'csv') {
                return Excel::download($export, 'data_siswa_' . now()->format('Y-m-d') . '.csv', \Maatwebsite\Excel\Excel::CSV);
            } else {
                return Excel::download($export, 'data_siswa_' . now()->format('Y-m-d') . '.xlsx', \Maatwebsite\Excel\Excel::XLSX);
            }

        } catch (\Exception $e) {
            \Log::error('Export students error: ' . $e->getMessage());
            \Log::error('Export students stack trace: ' . $e->getTraceAsString());
            
            // Return error as JSON for debugging
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan saat mengexport data siswa: ' . $e->getMessage(),
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ], 500);
        }
    }

    public function exportEmployees(Request $request)
    {
        try {
            $userId = $request->user()->id;
            $format = $request->get('format', 'xlsx');
            $jabatan = $request->get('jabatan');
            $status = $request->get('status');

            // Build query with filters
            $query = Employee::with(['user', 'identity', 'contact', 'families']);
            
            if ($jabatan) {
                $query->where('jabatan', $jabatan);
            }
            
            if ($status) {
                $query->where('status', $status);
            }

            $employees = $query->get();
            
            // Log export activity
            AuditService::log('EXPORT_EMPLOYEES', [
                'event_type' => 'data_export',
                'export_type' => 'employees',
                'total_count' => $employees->count(),
                'filters' => [
                    'jabatan' => $jabatan,
                    'status' => $status,
                    'format' => $format
                ],
                'timestamp' => now()->toISOString()
            ], $request->user());

            // Log export history
            $this->logExportHistory('employees', 'data_pegawai', $employees->count(), $userId, $format);

            $export = new EmployeeExport($employees);
            
            if ($format === 'csv') {
                return Excel::download($export, 'data_pegawai_' . now()->format('Y-m-d') . '.csv', \Maatwebsite\Excel\Excel::CSV);
            } else {
                return Excel::download($export, 'data_pegawai_' . now()->format('Y-m-d') . '.xlsx', \Maatwebsite\Excel\Excel::XLSX);
            }

        } catch (\Exception $e) {
            \Log::error('Export employees error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan saat mengexport data pegawai: ' . $e->getMessage()
            ], 500);
        }
    }

    public function exportAuditTrail(Request $request)
    {
        try {
            $userId = $request->user()->id;
            $format = $request->get('format', 'xlsx');
            $dateFrom = $request->get('date_from');
            $dateTo = $request->get('date_to');
            $eventType = $request->get('event_type');

            // Build query with filters
            $query = AuditLog::with(['user']);
            
            if ($dateFrom) {
                $query->whereDate('created_at', '>=', $dateFrom);
            }
            
            if ($dateTo) {
                $query->whereDate('created_at', '<=', $dateTo);
            }
            
            if ($eventType) {
                $query->where('event_type', $eventType);
            }

            $auditLogs = $query->orderBy('created_at', 'desc')->get();
            
            // Log export activity
            AuditService::log('EXPORT_AUDIT_TRAIL', [
                'event_type' => 'data_export',
                'export_type' => 'audit_trail',
                'total_count' => $auditLogs->count(),
                'filters' => [
                    'date_from' => $dateFrom,
                    'date_to' => $dateTo,
                    'event_type' => $eventType,
                    'format' => $format
                ],
                'timestamp' => now()->toISOString()
            ], $request->user());

            // Log export history
            $this->logExportHistory('audit_trail', 'audit_trail', $auditLogs->count(), $userId, $format);

            $export = new AuditTrailExport($auditLogs);
            
            if ($format === 'csv') {
                return Excel::download($export, 'audit_trail_' . now()->format('Y-m-d') . '.csv', \Maatwebsite\Excel\Excel::CSV);
            } else {
                return Excel::download($export, 'audit_trail_' . now()->format('Y-m-d') . '.xlsx', \Maatwebsite\Excel\Excel::XLSX);
            }

        } catch (\Exception $e) {
            \Log::error('Export audit trail error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan saat mengexport audit trail: ' . $e->getMessage()
            ], 500);
        }
    }

    public function getExportHistory(Request $request)
    {
        try {
            $userId = $request->user()->id;
            
            $history = DB::table('export_logs')
                ->where('user_id', $userId)
                ->orderBy('created_at', 'desc')
                ->get();

            return response()->json([
                'success' => true,
                'data' => $history
            ]);

        } catch (\Exception $e) {
            \Log::error('Get export history error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil riwayat export'
            ], 500);
        }
    }

    private function logExportHistory(string $type, string $fileName, int $totalCount, int $userId, string $format): void
    {
        DB::table('export_logs')->insert([
            'type' => $type,
            'file_name' => $fileName . '_' . now()->format('Y-m-d') . '.' . $format,
            'total_count' => $totalCount,
            'format' => $format,
            'status' => 'success',
            'user_id' => $userId,
            'created_at' => now(),
            'updated_at' => now()
        ]);
    }
}
