<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Employee;
use App\Models\User;
use App\Models\Role;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class EmployeeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get Guru role
        $guruRole = Role::where('name', 'Guru')->first();
        if (!$guruRole) {
            $guruRole = Role::create([
                'name' => 'Guru',
                'description' => 'Guru dengan akses terbatas'
            ]);
        }

        $employees = [
            [
                'nip' => '196501011990031001',
                'nama' => 'Dr. Siti Nurhaliza, S.Pd., M.Pd.',
                'jabatan' => 'Guru Matematika',
                'status' => 'aktif',
                'username' => 'guru_math_001',
                'name' => 'Dr. Siti Nurhaliza',
                'email' => 'siti.nurhaliza@sekolah.id',
                'password' => 'guru_math_001',
                'identity' => [
                    'nik' => '196501011990031001',
                    'tempat_lahir' => 'Jakarta',
                    'tanggal_lahir' => '1965-01-01',
                    'jenis_kelamin' => 'P',
                    'agama' => 'Islam'
                ],
                'contact' => [
                    'alamat' => 'Jl. Merdeka No. 123',
                    'kota' => 'Jakarta',
                    'provinsi' => 'DKI Jakarta',
                    'kode_pos' => '10110',
                    'no_hp' => '081234567890',
                    'email' => 'siti.nurhaliza@sekolah.id'
                ]
            ],
            [
                'nip' => '197203101998031002',
                'nama' => 'Budi Santoso, S.Pd.',
                'jabatan' => 'Guru Bahasa Indonesia',
                'status' => 'aktif',
                'username' => 'guru_bindo_002',
                'name' => 'Budi Santoso',
                'email' => 'budi.santoso@sekolah.id',
                'password' => 'guru_bindo_002',
                'identity' => [
                    'nik' => '197203101998031002',
                    'tempat_lahir' => 'Bandung',
                    'tanggal_lahir' => '1972-03-10',
                    'jenis_kelamin' => 'L',
                    'agama' => 'Islam'
                ],
                'contact' => [
                    'alamat' => 'Jl. Sudirman No. 456',
                    'kota' => 'Bandung',
                    'provinsi' => 'Jawa Barat',
                    'kode_pos' => '40111',
                    'no_hp' => '081234567891',
                    'email' => 'budi.santoso@sekolah.id'
                ]
            ],
            [
                'nip' => '197508201999031003',
                'nama' => 'Dr. Ahmad Wijaya, S.Pd., M.Pd.',
                'jabatan' => 'Guru IPA',
                'status' => 'aktif',
                'username' => 'guru_ipa_003',
                'name' => 'Dr. Ahmad Wijaya',
                'email' => 'ahmad.wijaya@sekolah.id',
                'password' => 'guru_ipa_003',
                'identity' => [
                    'nik' => '197508201999031003',
                    'tempat_lahir' => 'Surabaya',
                    'tanggal_lahir' => '1975-08-20',
                    'jenis_kelamin' => 'L',
                    'agama' => 'Islam'
                ],
                'contact' => [
                    'alamat' => 'Jl. Diponegoro No. 789',
                    'kota' => 'Surabaya',
                    'provinsi' => 'Jawa Timur',
                    'kode_pos' => '60241',
                    'no_hp' => '081234567892',
                    'email' => 'ahmad.wijaya@sekolah.id'
                ]
            ],
            [
                'nip' => '198012151999032004',
                'nama' => 'Sari Dewi, S.Pd., M.Pd.',
                'jabatan' => 'Guru Bahasa Inggris',
                'status' => 'aktif',
                'username' => 'guru_inggris_004',
                'name' => 'Sari Dewi',
                'email' => 'sari.dewi@sekolah.id',
                'password' => 'guru_inggris_004',
                'identity' => [
                    'nik' => '198012151999032004',
                    'tempat_lahir' => 'Yogyakarta',
                    'tanggal_lahir' => '1980-12-15',
                    'jenis_kelamin' => 'P',
                    'agama' => 'Islam'
                ],
                'contact' => [
                    'alamat' => 'Jl. Malioboro No. 321',
                    'kota' => 'Yogyakarta',
                    'provinsi' => 'DI Yogyakarta',
                    'kode_pos' => '55111',
                    'no_hp' => '081234567893',
                    'email' => 'sari.dewi@sekolah.id'
                ]
            ],
            [
                'nip' => '198503201999031005',
                'nama' => 'Rudi Hartono, S.Pd.',
                'jabatan' => 'Guru Olahraga',
                'status' => 'aktif',
                'username' => 'guru_olahraga_005',
                'name' => 'Rudi Hartono',
                'email' => 'rudi.hartono@sekolah.id',
                'password' => 'guru_olahraga_005',
                'identity' => [
                    'nik' => '198503201999031005',
                    'tempat_lahir' => 'Semarang',
                    'tanggal_lahir' => '1985-03-20',
                    'jenis_kelamin' => 'L',
                    'agama' => 'Islam'
                ],
                'contact' => [
                    'alamat' => 'Jl. Pemuda No. 654',
                    'kota' => 'Semarang',
                    'provinsi' => 'Jawa Tengah',
                    'kode_pos' => '50111',
                    'no_hp' => '081234567894',
                    'email' => 'rudi.hartono@sekolah.id'
                ]
            ],
            [
                'nip' => '198708251999032006',
                'nama' => 'Maya Sari, S.Pd., M.Pd.',
                'jabatan' => 'Guru Seni Budaya',
                'status' => 'aktif',
                'username' => 'guru_seni_006',
                'name' => 'Maya Sari',
                'email' => 'maya.sari@sekolah.id',
                'password' => 'guru_seni_006',
                'identity' => [
                    'nik' => '198708251999032006',
                    'tempat_lahir' => 'Medan',
                    'tanggal_lahir' => '1987-08-25',
                    'jenis_kelamin' => 'P',
                    'agama' => 'Kristen'
                ],
                'contact' => [
                    'alamat' => 'Jl. Gatot Subroto No. 987',
                    'kota' => 'Medan',
                    'provinsi' => 'Sumatera Utara',
                    'kode_pos' => '20111',
                    'no_hp' => '081234567895',
                    'email' => 'maya.sari@sekolah.id'
                ]
            ],
            [
                'nip' => '199001101999031007',
                'nama' => 'Andi Pratama, S.Pd.',
                'jabatan' => 'Guru PPKN',
                'status' => 'aktif',
                'username' => 'guru_ppkn_007',
                'name' => 'Andi Pratama',
                'email' => 'andi.pratama@sekolah.id',
                'password' => 'guru_ppkn_007',
                'identity' => [
                    'nik' => '199001101999031007',
                    'tempat_lahir' => 'Palembang',
                    'tanggal_lahir' => '1990-01-10',
                    'jenis_kelamin' => 'L',
                    'agama' => 'Islam'
                ],
                'contact' => [
                    'alamat' => 'Jl. Sudirman No. 147',
                    'kota' => 'Palembang',
                    'provinsi' => 'Sumatera Selatan',
                    'kode_pos' => '30111',
                    'no_hp' => '081234567896',
                    'email' => 'andi.pratama@sekolah.id'
                ]
            ],
            [
                'nip' => '199205151999032008',
                'nama' => 'Dewi Kartika, S.Pd., M.Pd.',
                'jabatan' => 'Guru Sejarah',
                'status' => 'aktif',
                'username' => 'guru_sejarah_008',
                'name' => 'Dewi Kartika',
                'email' => 'dewi.kartika@sekolah.id',
                'password' => 'guru_sejarah_008',
                'identity' => [
                    'nik' => '199205151999032008',
                    'tempat_lahir' => 'Makassar',
                    'tanggal_lahir' => '1992-05-15',
                    'jenis_kelamin' => 'P',
                    'agama' => 'Islam'
                ],
                'contact' => [
                    'alamat' => 'Jl. Veteran No. 258',
                    'kota' => 'Makassar',
                    'provinsi' => 'Sulawesi Selatan',
                    'kode_pos' => '90111',
                    'no_hp' => '081234567897',
                    'email' => 'dewi.kartika@sekolah.id'
                ]
            ],
            [
                'nip' => '199408201999031009',
                'nama' => 'Eko Prasetyo, S.Pd.',
                'jabatan' => 'Guru Geografi',
                'status' => 'aktif',
                'username' => 'guru_geografi_009',
                'name' => 'Eko Prasetyo',
                'email' => 'eko.prasetyo@sekolah.id',
                'password' => 'guru_geografi_009',
                'identity' => [
                    'nik' => '199408201999031009',
                    'tempat_lahir' => 'Denpasar',
                    'tanggal_lahir' => '1994-08-20',
                    'jenis_kelamin' => 'L',
                    'agama' => 'Hindu'
                ],
                'contact' => [
                    'alamat' => 'Jl. Raya Ubud No. 369',
                    'kota' => 'Denpasar',
                    'provinsi' => 'Bali',
                    'kode_pos' => '80111',
                    'no_hp' => '081234567898',
                    'email' => 'eko.prasetyo@sekolah.id'
                ]
            ],
            [
                'nip' => '199612251999032010',
                'nama' => 'Fitriani, S.Pd., M.Pd.',
                'jabatan' => 'Guru Ekonomi',
                'status' => 'aktif',
                'username' => 'guru_ekonomi_010',
                'name' => 'Fitriani',
                'email' => 'fitriani@sekolah.id',
                'password' => 'guru_ekonomi_010',
                'identity' => [
                    'nik' => '199612251999032010',
                    'tempat_lahir' => 'Pontianak',
                    'tanggal_lahir' => '1996-12-25',
                    'jenis_kelamin' => 'P',
                    'agama' => 'Islam'
                ],
                'contact' => [
                    'alamat' => 'Jl. Ahmad Yani No. 741',
                    'kota' => 'Pontianak',
                    'provinsi' => 'Kalimantan Barat',
                    'kode_pos' => '78111',
                    'no_hp' => '081234567899',
                    'email' => 'fitriani@sekolah.id'
                ]
            ]
        ];

        foreach ($employees as $employeeData) {
            // Create user
            $user = User::create([
                'username' => $employeeData['username'],
                'name' => $employeeData['name'],
                'email' => $employeeData['email'],
                'password' => Hash::make($employeeData['password']),
                'role_id' => $guruRole->id,
            ]);

            // Create employee
            $employee = Employee::create([
                'user_id' => $user->id,
                'nip' => $employeeData['nip'],
                'nama' => $employeeData['nama'],
                'jabatan' => $employeeData['jabatan'],
                'status' => $employeeData['status'],
                'qr_value' => $employeeData['nip'], // Use NIP as QR value
            ]);

            // Create identity if data exists
            if (!empty($employeeData['identity'])) {
                $employee->identity()->create($employeeData['identity']);
            }

            // Create contact if data exists
            if (!empty($employeeData['contact'])) {
                $employee->contact()->create($employeeData['contact']);
            }
        }

        $this->command->info('10 employees created successfully!');
    }
}
