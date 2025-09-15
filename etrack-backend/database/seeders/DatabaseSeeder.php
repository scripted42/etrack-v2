<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\Permission;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Create roles
        $adminRole = Role::create([
            'name' => 'Admin',
            'description' => 'Administrator sistem dengan akses penuh'
        ]);

        $kepalaSekolahRole = Role::create([
            'name' => 'Kepala Sekolah',
            'description' => 'Kepala sekolah dengan akses laporan dan monitoring'
        ]);

        $tuRole = Role::create([
            'name' => 'TU/Operator',
            'description' => 'Tata Usaha/Operator dengan akses CRUD data siswa dan pegawai'
        ]);

        $guruRole = Role::create([
            'name' => 'Guru',
            'description' => 'Guru dengan akses profil siswa terbatas'
        ]);

        $siswaRole = Role::create([
            'name' => 'Siswa',
            'description' => 'Siswa dengan akses data pribadi saja'
        ]);

        // Create permissions
        $permissions = [
            ['name' => 'view_dashboard', 'description' => 'Melihat dashboard'],
            ['name' => 'manage_users', 'description' => 'Mengelola pengguna'],
            ['name' => 'manage_students', 'description' => 'Mengelola data siswa'],
            ['name' => 'manage_employees', 'description' => 'Mengelola data pegawai'],
            ['name' => 'view_reports', 'description' => 'Melihat laporan'],
            ['name' => 'manage_documents', 'description' => 'Mengelola dokumen'],
            ['name' => 'view_audit_logs', 'description' => 'Melihat audit log'],
            ['name' => 'view_profile', 'description' => 'Melihat profil sendiri'],
        ];

        foreach ($permissions as $permission) {
            Permission::create($permission);
        }

        // Assign permissions to roles
        $adminPermissions = Permission::all();
        $adminRole->permissions()->attach($adminPermissions);

        $kepalaSekolahPermissions = Permission::whereIn('name', [
            'view_dashboard', 'view_reports', 'view_audit_logs', 'view_profile'
        ])->get();
        $kepalaSekolahRole->permissions()->attach($kepalaSekolahPermissions);

        $tuPermissions = Permission::whereIn('name', [
            'view_dashboard', 'manage_students', 'manage_employees', 'manage_documents', 'view_profile'
        ])->get();
        $tuRole->permissions()->attach($tuPermissions);

        $guruPermissions = Permission::whereIn('name', [
            'view_dashboard', 'view_students', 'view_profile'
        ])->get();
        $guruRole->permissions()->attach($guruPermissions);

        $siswaPermissions = Permission::whereIn('name', [
            'view_profile'
        ])->get();
        $siswaRole->permissions()->attach($siswaPermissions);

        // Create admin user
        User::firstOrCreate([
            'username' => 'admin'
        ], [
            'username' => 'admin',
            'name' => 'Administrator',
            'email' => 'admin@smpn14sby.sch.id',
            'password' => Hash::make('password123'),
            'role_id' => $adminRole->id,
            'status' => 'aktif',
        ]);

        // Create kepala sekolah user
        User::firstOrCreate([
            'username' => 'kepsek'
        ], [
            'username' => 'kepsek',
            'name' => 'Kepala Sekolah',
            'email' => 'kepsek@smpn14sby.sch.id',
            'password' => Hash::make('password123'),
            'role_id' => $kepalaSekolahRole->id,
            'status' => 'aktif',
        ]);

        // Create TU user
        User::firstOrCreate([
            'username' => 'tu'
        ], [
            'username' => 'tu',
            'name' => 'Tata Usaha',
            'email' => 'tu@smpn14sby.sch.id',
            'password' => Hash::make('password123'),
            'role_id' => $tuRole->id,
            'status' => 'aktif',
        ]);
    }
}