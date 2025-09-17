<?php

namespace App\Imports;

use App\Models\Student;
use App\Models\User;
use App\Models\Role;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class SimpleStudentImport implements ToModel, WithHeadingRow
{
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
        \Log::info('Processing row:', $row);

        try {
            // Validate required fields
            if (empty($row['nama']) || empty($row['nis'])) {
                $this->failedCount++;
                $this->importErrors[] = "Nama dan NIS wajib diisi";
                return null;
            }

            // Check if student already exists
            if (Student::where('nis', $row['nis'])->exists()) {
                $this->failedCount++;
                $this->importErrors[] = "Siswa dengan NIS " . $row['nis'] . " sudah ada";
                return null;
            }

            // Check if user already exists
            if (User::where('username', $row['nis'])->exists()) {
                $this->failedCount++;
                $this->importErrors[] = "User dengan username " . $row['nis'] . " sudah ada";
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
                    'email' => $row['email'] ?? $row['nis'] . '@student.local',
                    'password' => Hash::make('password123'), // Default password
                    'role_id' => $studentRole->id,
                    'status' => '1',
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

                DB::commit();
                $this->importedCount++;
                
                \Log::info('Student imported successfully:', ['nis' => $row['nis'], 'nama' => $row['nama']]);
                
                return $student;

            } catch (\Exception $e) {
                DB::rollback();
                throw $e;
            }

        } catch (\Exception $e) {
            $this->failedCount++;
            $this->importErrors[] = $e->getMessage();
            \Log::error('Import error:', ['error' => $e->getMessage(), 'row' => $row]);
            return null;
        }
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
}
