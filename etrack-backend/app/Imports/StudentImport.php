<?php

namespace App\Imports;

use App\Models\Student;
use App\Models\User;
use App\Models\Role;
use App\Models\AuditLog;
use App\Services\AuditService;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
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
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterImport;
use Carbon\Carbon;

class StudentImport implements ToModel, WithHeadingRow, WithValidation, SkipsOnError, SkipsOnFailure, WithBatchInserts, WithChunkReading, WithEvents
{
    use Importable, SkipsErrors, SkipsFailures;

    private $importedCount = 0;
    private $failedCount = 0;
    private $importErrors = [];
    private $userId;

    public function __construct($userId = null)
    {
        $this->userId = $userId;
    }

    public function model(array $row)
    {
        try {
            // Debug logging
            \Log::info('Processing row:', $row);
            \Log::info('Row keys:', array_keys($row));
            
            // Validate required fields
            if (empty($row['nama']) || empty($row['nis'])) {
                $this->failedCount++;
                $this->importErrors[] = "Baris " . ($this->importedCount + $this->failedCount + 1) . ": Nama dan NIS wajib diisi";
                return null;
            }

            // Check if student already exists
            if (Student::where('nis', $row['nis'])->exists()) {
                $this->failedCount++;
                $this->importErrors[] = "Baris " . ($this->importedCount + $this->failedCount + 1) . ": Siswa dengan NIS " . $row['nis'] . " sudah ada";
                return null;
            }

            // Check if user already exists
            if (User::where('username', $row['nis'])->exists()) {
                $this->failedCount++;
                $this->importErrors[] = "Baris " . ($this->importedCount + $this->failedCount + 1) . ": User dengan username " . $row['nis'] . " sudah ada";
                return null;
            }

            DB::beginTransaction();

            try {
                // Get student role
                $studentRole = Role::where('name', 'student')->first();
                if (!$studentRole) {
                    throw new \Exception('Role student tidak ditemukan');
                }

                // Create user account
                $user = User::create([
                    'username' => $row['nis'],
                    'name' => $row['nama'],
                    'email' => $row['email'] ?? null,
                    'password' => Hash::make('password123'), // Default password
                    'role_id' => $studentRole->id,
                    'status' => 'active',
                    'created_at' => now(),
                    'updated_at' => now()
                ]);

                // Create student profile
                $student = Student::create([
                    'user_id' => $user->id,
                    'nis' => $row['nis'],
                    'nama' => $row['nama'],
                    'kelas' => $row['kelas'] ?? null,
                    'status' => $row['status'] ?? 'aktif',
                    'created_at' => now(),
                    'updated_at' => now()
                ]);

                // Create student identity if data available
                if (!empty($row['nisn']) || !empty($row['tempat_lahir']) || !empty($row['tanggal_lahir'])) {
                    \DB::table('student_identities')->insert([
                        'student_id' => $student->id,
                        'nisn' => $row['nisn'] ?? null,
                        'tempat_lahir' => $row['tempat_lahir'] ?? null,
                        'tanggal_lahir' => !empty($row['tanggal_lahir']) ? $this->parseDate($row['tanggal_lahir']) : null,
                        'jenis_kelamin' => $row['jenis_kelamin'] ?? null,
                        'agama' => $row['agama'] ?? null,
                        'created_at' => now(),
                        'updated_at' => now()
                    ]);
                }

                // Create student contact if data available
                if (!empty($row['no_hp'])) {
                    \DB::table('student_contacts')->insert([
                        'student_id' => $student->id,
                        'no_hp' => $row['no_hp'] ?? null,
                        'created_at' => now(),
                        'updated_at' => now()
                    ]);
                }

                // Create student guardian if data available
                if (!empty($row['wali_nama']) || !empty($row['wali_hubungan'])) {
                    \DB::table('student_guardians')->insert([
                        'student_id' => $student->id,
                        'nama' => $row['wali_nama'] ?? null,
                        'hubungan' => $row['wali_hubungan'] ?? null,
                        'no_hp' => $row['wali_no_hp'] ?? null,
                        'created_at' => now(),
                        'updated_at' => now()
                    ]);
                }

                // Log audit
                if ($this->userId) {
                    AuditService::log($this->userId, 'CREATE_STUDENT', [
                        'event_type' => 'crud_operation',
                        'model' => 'Student',
                        'model_id' => $student->id,
                        'operation' => 'create',
                        'nis' => $student->nis,
                        'nama' => $student->nama,
                        'kelas' => $student->kelas,
                        'status' => $student->status,
                        'timestamp' => now()->toISOString()
                    ]);
                }

                DB::commit();
                $this->importedCount++;
                return $student;

            } catch (\Exception $e) {
                DB::rollback();
                throw $e;
            }

        } catch (\Exception $e) {
            $this->failedCount++;
            $this->importErrors[] = "Baris " . ($this->importedCount + $this->failedCount + 1) . ": " . $e->getMessage();
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
            'nisn' => 'nullable|string|max:20',
            'tempat_lahir' => 'nullable|string|max:100',
            'tanggal_lahir' => 'nullable|string',
            'jenis_kelamin' => 'nullable|string|in:L,P',
            'agama' => 'nullable|string|max:50',
            'no_hp' => 'nullable|string|max:20',
            'wali_nama' => 'nullable|string|max:255',
            'wali_hubungan' => 'nullable|string|max:50',
            'wali_no_hp' => 'nullable|string|max:20',
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

    public function registerEvents(): array
    {
        return [
            AfterImport::class => function(AfterImport $event) {
                // Log import completion
                if ($this->userId) {
                    AuditService::log('IMPORT_STUDENTS', [
                        'event_type' => 'data_import',
                        'import_type' => 'students',
                        'imported_count' => $this->importedCount,
                        'failed_count' => $this->failedCount,
                        'total_errors' => count($this->importErrors),
                        'timestamp' => now()->toISOString()
                    ], User::find($this->userId));
                }
            },
        ];
    }

    public function getImportedCount(): int
    {
        return $this->importedCount;
    }

    public function getFailedCount(): int
    {
        return $this->failedCount;
    }

    public function getImportErrors(): array
    {
        return $this->importErrors;
    }

    public function getErrors(): array
    {
        return $this->importErrors;
    }

    /**
     * Parse date from various formats
     */
    private function parseDate($dateString)
    {
        try {
            // Try different date formats
            $formats = ['d/m/Y', 'd-m-Y', 'Y-m-d', 'd/m/y', 'd-m-y'];
            
            foreach ($formats as $format) {
                try {
                    return Carbon::createFromFormat($format, $dateString)->format('Y-m-d');
                } catch (\Exception $e) {
                    continue;
                }
            }
            
            // If all formats fail, try to parse as is
            return Carbon::parse($dateString)->format('Y-m-d');
        } catch (\Exception $e) {
            return null;
        }
    }
}
