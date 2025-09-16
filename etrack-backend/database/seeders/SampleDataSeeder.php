<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;

class SampleDataSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create roles
        $adminRoleId = DB::table('roles')->insertGetId([
            'name' => 'Admin',
            'description' => 'Administrator dengan akses penuh',
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ]);

        $teacherRoleId = DB::table('roles')->insertGetId([
            'name' => 'Guru',
            'description' => 'Guru dengan akses terbatas',
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ]);

        $studentRoleId = DB::table('roles')->insertGetId([
            'name' => 'Siswa',
            'description' => 'Siswa dengan akses terbatas',
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ]);

        $staffRoleId = DB::table('roles')->insertGetId([
            'name' => 'TU',
            'description' => 'Tata Usaha dengan akses data',
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ]);

        // Create admin user
        $adminUserId = DB::table('users')->insertGetId([
            'username' => 'admin',
            'name' => 'Administrator',
            'email' => 'admin@sekolah.local',
            'password' => Hash::make('password'),
            'role_id' => $adminRoleId,
            'status' => 'aktif',
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ]);

        // Create teacher user
        $teacherUserId = DB::table('users')->insertGetId([
            'username' => 'guru1',
            'name' => 'Budi Santoso',
            'email' => 'budi@sekolah.local',
            'password' => Hash::make('password'),
            'role_id' => $teacherRoleId,
            'status' => 'aktif',
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ]);

        // Create staff user
        $staffUserId = DB::table('users')->insertGetId([
            'username' => 'tu1',
            'name' => 'Siti Nurhaliza',
            'email' => 'siti@sekolah.local',
            'password' => Hash::make('password'),
            'role_id' => $staffRoleId,
            'status' => 'aktif',
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ]);

        // Create employee (teacher)
        $employeeId = DB::table('employees')->insertGetId([
            'user_id' => $teacherUserId,
            'nip' => '196512011990031001',
            'nama' => 'Budi Santoso',
            'jabatan' => 'Guru Matematika',
            'status' => 'aktif',
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ]);

        // Create employee (staff)
        $staffEmployeeId = DB::table('employees')->insertGetId([
            'user_id' => $staffUserId,
            'nip' => '197203151995032002',
            'nama' => 'Siti Nurhaliza',
            'jabatan' => 'Kepala Tata Usaha',
            'status' => 'aktif',
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ]);

        // Create sample students
        $studentData = [
            [
                'nis' => '2024001',
                'nama' => 'Ahmad Rizki',
                'kelas' => 'X IPA 1',
                'jurusan' => 'IPA',
                'status' => 'aktif',
            ],
            [
                'nis' => '2024002',
                'nama' => 'Sari Dewi',
                'kelas' => 'X IPA 2',
                'jurusan' => 'IPA',
                'status' => 'aktif',
            ],
            [
                'nis' => '2024003',
                'nama' => 'Muhammad Ali',
                'kelas' => 'XI IPS 1',
                'jurusan' => 'IPS',
                'status' => 'aktif',
            ],
            [
                'nis' => '2024004',
                'nama' => 'Putri Maharani',
                'kelas' => 'XII IPA 1',
                'jurusan' => 'IPA',
                'status' => 'aktif',
            ],
            [
                'nis' => '2024005',
                'nama' => 'Rizki Pratama',
                'kelas' => 'XII IPS 1',
                'jurusan' => 'IPS',
                'status' => 'aktif',
            ],
        ];

        foreach ($studentData as $index => $student) {
            $studentUserId = DB::table('users')->insertGetId([
                'username' => 'siswa' . ($index + 1),
                'name' => $student['nama'],
                'email' => 'siswa' . ($index + 1) . '@sekolah.local',
                'password' => Hash::make('password'),
                'role_id' => $studentRoleId,
                'status' => 'aktif',
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ]);

            $studentId = DB::table('students')->insertGetId([
                'user_id' => $studentUserId,
                'nis' => $student['nis'],
                'nama' => $student['nama'],
                'kelas' => $student['kelas'],
                'jurusan' => $student['jurusan'],
                'status' => $student['status'],
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ]);

            // Create student identity
            DB::table('student_identities')->insert([
                'student_id' => $studentId,
                'nik' => '320123456789000' . ($index + 1),
                'nisn' => '123456789' . ($index + 1),
                'tempat_lahir' => 'Jakarta',
                'tanggal_lahir' => Carbon::now()->subYears(16 + $index)->format('Y-m-d'),
                'jenis_kelamin' => $index % 2 == 0 ? 'L' : 'P',
                'agama' => 'Islam',
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ]);

            // Create student contact
            DB::table('student_contacts')->insert([
                'student_id' => $studentId,
                'alamat' => 'Jl. Contoh No. ' . ($index + 1),
                'kota' => 'Jakarta',
                'provinsi' => 'DKI Jakarta',
                'kode_pos' => '12345',
                'no_hp' => '08123456789' . ($index + 1),
                'email' => 'siswa' . ($index + 1) . '@sekolah.local',
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ]);
        }

        // Create employee identity for teacher
        DB::table('employee_identities')->insert([
            'employee_id' => $employeeId,
            'nik' => '196512011990031001',
            'tempat_lahir' => 'Bandung',
            'tanggal_lahir' => '1965-12-01',
            'jenis_kelamin' => 'L',
            'agama' => 'Islam',
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ]);

        // Create employee contact for teacher
        DB::table('employee_contacts')->insert([
            'employee_id' => $employeeId,
            'alamat' => 'Jl. Guru No. 123',
            'kota' => 'Jakarta',
            'provinsi' => 'DKI Jakarta',
            'kode_pos' => '12345',
            'no_hp' => '081234567890',
            'email' => 'budi@sekolah.local',
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ]);

        $this->command->info('Sample data berhasil dibuat!');
        $this->command->info('Admin: admin@sekolah.local / password');
        $this->command->info('Guru: budi@sekolah.local / password');
        $this->command->info('TU: siti@sekolah.local / password');
        $this->command->info('Siswa: siswa1@sekolah.local / password');
    }
}