<?php

namespace App\Imports;

use App\Models\Employee;
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

class EmployeeImport implements ToModel, WithHeadingRow, WithValidation, SkipsOnError, SkipsOnFailure, WithBatchInserts, WithChunkReading, WithEvents
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
            // Validate required fields
            if (empty($row['nama']) || empty($row['nip'])) {
                $this->failedCount++;
                $this->importErrors[] = "Baris " . ($this->importedCount + $this->failedCount + 1) . ": Nama dan NIP wajib diisi";
                return null;
            }

            // Check if employee already exists
            if (Employee::where('nip', $row['nip'])->exists()) {
                $this->failedCount++;
                $this->importErrors[] = "Baris " . ($this->importedCount + $this->failedCount + 1) . ": Pegawai dengan NIP " . $row['nip'] . " sudah ada";
                return null;
            }

            // Check if user already exists
            if (User::where('username', $row['nip'])->exists()) {
                $this->failedCount++;
                $this->importErrors[] = "Baris " . ($this->importedCount + $this->failedCount + 1) . ": User dengan username " . $row['nip'] . " sudah ada";
                return null;
            }

            DB::beginTransaction();

            try {
                // Get employee role
                $employeeRole = Role::where('name', 'employee')->first();
                if (!$employeeRole) {
                    throw new \Exception('Role employee tidak ditemukan');
                }

                // Create user account
                $user = User::create([
                    'username' => $row['nip'],
                    'name' => $row['nama'],
                    'email' => $row['email'] ?? null,
                    'password' => Hash::make('password123'), // Default password
                    'role_id' => $employeeRole->id,
                    'status' => 'active',
                    'created_at' => now(),
                    'updated_at' => now()
                ]);

                // Create employee profile
                $employee = Employee::create([
                    'user_id' => $user->id,
                    'nip' => $row['nip'],
                    'nama' => $row['nama'],
                    'jabatan' => $row['jabatan'] ?? 'Guru',
                    'status' => $row['status'] ?? 'aktif',
                    'created_at' => now(),
                    'updated_at' => now()
                ]);

                // Log audit
                if ($this->userId) {
                    AuditService::log('CREATE_EMPLOYEE', [
                        'event_type' => 'crud_operation',
                        'model' => 'Employee',
                        'model_id' => $employee->id,
                        'operation' => 'create',
                        'nip' => $employee->nip,
                        'nama' => $employee->nama,
                        'jabatan' => $employee->jabatan,
                        'status' => $employee->status,
                        'timestamp' => now()->toISOString()
                    ], User::find($this->userId));
                }

                DB::commit();
                $this->importedCount++;
                return $employee;

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
            'nip' => 'required|string|max:20',
            'nama' => 'required|string|max:255',
            'jabatan' => 'nullable|string|max:100',
            'status' => 'nullable|string|in:aktif,nonaktif',
            'email' => 'nullable|email|max:255',
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
                    AuditService::log('IMPORT_EMPLOYEES', [
                        'event_type' => 'data_import',
                        'import_type' => 'employees',
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
}
