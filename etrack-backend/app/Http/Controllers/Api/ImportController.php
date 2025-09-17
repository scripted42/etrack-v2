<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Student;
use App\Models\Employee;
use App\Models\User;
use App\Models\Role;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;
use Maatwebsite\Excel\Concerns\Importable;
use Maatwebsite\Excel\Concerns\SkipsOnError;
use Maatwebsite\Excel\Concerns\SkipsErrors;
use Maatwebsite\Excel\Concerns\SkipsOnFailure;
use Maatwebsite\Excel\Concerns\SkipsFailures;
use Maatwebsite\Excel\Concerns\WithBatchInserts;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Carbon\Carbon;

class ImportController extends Controller
{
    /**
     * Import students from Excel/CSV
     */
    public function importStudents(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'file' => 'required|file|mimes:xlsx,xls,csv|max:10240', // 10MB max
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'File tidak valid',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $file = $request->file('file');
            $import = new StudentImport();
            
            Excel::import($import, $file);

            return response()->json([
                'success' => true,
                'message' => 'Import siswa berhasil',
                'data' => [
                    'imported_count' => $import->getImportedCount(),
                    'failed_count' => $import->getFailedCount(),
                    'errors' => $import->getErrors()
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengimport data siswa',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Import employees from Excel/CSV
     */
    public function importEmployees(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'file' => 'required|file|mimes:xlsx,xls,csv|max:10240', // 10MB max
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'File tidak valid',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $file = $request->file('file');
            $import = new EmployeeImport();
            
            Excel::import($import, $file);

            return response()->json([
                'success' => true,
                'message' => 'Import pegawai berhasil',
                'data' => [
                    'imported_count' => $import->getImportedCount(),
                    'failed_count' => $import->getFailedCount(),
                    'errors' => $import->getErrors()
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengimport data pegawai',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Download template for student import
     */
    public function downloadStudentTemplate()
    {
        try {
            $template = new StudentTemplateExport();
            $fileName = 'template_import_siswa_' . date('Y-m-d_H-i-s') . '.xlsx';
            
            return Excel::download($template, $fileName);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal membuat template',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Download template for employee import
     */
    public function downloadEmployeeTemplate()
    {
        try {
            $template = new EmployeeTemplateExport();
            $fileName = 'template_import_pegawai_' . date('Y-m-d_H-i-s') . '.xlsx';
            
            return Excel::download($template, $fileName);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal membuat template',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get import history
     */
    public function getImportHistory(Request $request): JsonResponse
    {
        try {
            $history = DB::table('import_logs')
                ->orderBy('created_at', 'desc')
                ->limit(50)
                ->get();

            return response()->json([
                'success' => true,
                'data' => $history
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil riwayat import',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}

/**
 * Student Import Class
 */
class StudentImport implements ToModel, WithHeadingRow, WithValidation, SkipsOnError, SkipsOnFailure, WithBatchInserts, WithChunkReading
{
    use Importable, SkipsErrors, SkipsFailures;

    private $importedCount = 0;
    private $failedCount = 0;
    private $errors = [];

    public function model(array $row)
    {
        try {
            // Validate required fields
            if (empty($row['nama']) || empty($row['nis'])) {
                $this->failedCount++;
                $this->errors[] = "Baris " . ($this->importedCount + $this->failedCount) . ": Nama dan NIS wajib diisi";
                return null;
            }

            // Check if student already exists
            if (Student::where('nis', $row['nis'])->exists()) {
                $this->failedCount++;
                $this->errors[] = "Baris " . ($this->importedCount + $this->failedCount) . ": Siswa dengan NIS " . $row['nis'] . " sudah ada";
                return null;
            }

            $student = new Student([
                'nis' => $row['nis'],
                'nama' => $row['nama'],
                'kelas' => $row['kelas'] ?? null,
                'status' => $row['status'] ?? 'aktif',
                'created_at' => now(),
                'updated_at' => now()
            ]);

            $this->importedCount++;
            return $student;

        } catch (\Exception $e) {
            $this->failedCount++;
            $this->errors[] = "Baris " . ($this->importedCount + $this->failedCount) . ": " . $e->getMessage();
            return null;
        }
    }

    public function rules(): array
    {
        return [
            'nis' => 'required|string|max:20',
            'nama' => 'required|string|max:255',
            'kelas' => 'nullable|string|max:10',
            'status' => 'nullable|string|in:aktif,nonaktif',
        ];
    }

    public function batchSize(): int
    {
        return 100;
    }

    public function chunkSize(): int
    {
        return 100;
    }

    public function getImportedCount(): int
    {
        return $this->importedCount;
    }

    public function getFailedCount(): int
    {
        return $this->failedCount;
    }

    public function getErrors(): array
    {
        return $this->errors;
    }
}

/**
 * Employee Import Class
 */
class EmployeeImport implements ToModel, WithHeadingRow, WithValidation, SkipsOnError, SkipsOnFailure, WithBatchInserts, WithChunkReading
{
    use Importable, SkipsErrors, SkipsFailures;

    private $importedCount = 0;
    private $failedCount = 0;
    private $errors = [];

    public function model(array $row)
    {
        try {
            // Validate required fields
            if (empty($row['nama']) || empty($row['nip'])) {
                $this->failedCount++;
                $this->errors[] = "Baris " . ($this->importedCount + $this->failedCount) . ": Nama dan NIP wajib diisi";
                return null;
            }

            // Check if employee already exists
            if (Employee::where('nip', $row['nip'])->exists()) {
                $this->failedCount++;
                $this->errors[] = "Baris " . ($this->importedCount + $this->failedCount) . ": Pegawai dengan NIP " . $row['nip'] . " sudah ada";
                return null;
            }

            $employee = new Employee([
                'nip' => $row['nip'],
                'nama' => $row['nama'],
                'jabatan' => $row['jabatan'] ?? 'Guru',
                'status' => $row['status'] ?? 'aktif',
                'created_at' => now(),
                'updated_at' => now()
            ]);

            $this->importedCount++;
            return $employee;

        } catch (\Exception $e) {
            $this->failedCount++;
            $this->errors[] = "Baris " . ($this->importedCount + $this->failedCount) . ": " . $e->getMessage();
            return null;
        }
    }

    public function rules(): array
    {
        return [
            'nip' => 'required|string|max:20',
            'nama' => 'required|string|max:255',
            'jabatan' => 'nullable|string|max:100',
            'status' => 'nullable|string|in:aktif,nonaktif',
        ];
    }

    public function batchSize(): int
    {
        return 100;
    }

    public function chunkSize(): int
    {
        return 100;
    }

    public function getImportedCount(): int
    {
        return $this->importedCount;
    }

    public function getFailedCount(): int
    {
        return $this->failedCount;
    }

    public function getErrors(): array
    {
        return $this->errors;
    }
}

/**
 * Student Template Export Class
 */
class StudentTemplateExport implements \Maatwebsite\Excel\Concerns\FromArray
{
    public function array(): array
    {
        return [
            ['nis', 'nama', 'kelas', 'status'],
            ['2024001', 'Ahmad Rizki', '7A', 'aktif'],
            ['2024002', 'Siti Nurhaliza', '7B', 'aktif'],
            ['2024003', 'Budi Santoso', '8A', 'aktif'],
        ];
    }
}

/**
 * Employee Template Export Class
 */
class EmployeeTemplateExport implements \Maatwebsite\Excel\Concerns\FromArray
{
    public function array(): array
    {
        return [
            ['nip', 'nama', 'jabatan', 'status'],
            ['196501011990031001', 'Dr. Suryadi, M.Pd', 'Kepala Sekolah', 'aktif'],
            ['196502021990031002', 'Dra. Siti Aminah, M.Pd', 'Wakil Kepala Sekolah', 'aktif'],
            ['196503031990031003', 'Ahmad Fauzi, S.Pd', 'Guru Matematika', 'aktif'],
        ];
    }
}
