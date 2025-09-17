<?php

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\User;
use App\Models\Employee;
use App\Models\Role;
use Illuminate\Support\Facades\Hash;

try {
    $role = Role::where('name', 'Guru')->first();
    if (!$role) {
        echo "Role Guru tidak ditemukan\n";
        exit;
    }

    // Employee 1: Guru Matematika
    $user1 = User::create([
        'username' => 'guru001',
        'name' => 'Dr. Siti Nurhaliza',
        'email' => 'siti@smpn14sby.sch.id',
        'password' => Hash::make('password123'),
        'role_id' => $role->id,
        'status' => 'aktif'
    ]);

    $emp1 = Employee::create([
        'user_id' => $user1->id,
        'nip' => '196512151990032001',
        'nama' => 'Dr. Siti Nurhaliza, S.Pd., M.Pd.',
        'jabatan' => 'Guru Matematika',
        'status' => 'aktif',
        'qr_value' => '196512151990032001'
    ]);

    echo "✅ Employee 1 created: " . $emp1->nama . "\n";

    // Employee 2: Guru Bahasa Indonesia
    $user2 = User::create([
        'username' => 'guru002',
        'name' => 'Budi Santoso',
        'email' => 'budi@smpn14sby.sch.id',
        'password' => Hash::make('password123'),
        'role_id' => $role->id,
        'status' => 'aktif'
    ]);

    $emp2 = Employee::create([
        'user_id' => $user2->id,
        'nip' => '197203101998031002',
        'nama' => 'Budi Santoso, S.Pd.',
        'jabatan' => 'Guru Bahasa Indonesia',
        'status' => 'aktif',
        'qr_value' => '197203101998031002'
    ]);

    echo "✅ Employee 2 created: " . $emp2->nama . "\n";

    // Employee 3: Guru IPA
    $user3 = User::create([
        'username' => 'guru003',
        'name' => 'Dr. Ahmad Wijaya',
        'email' => 'ahmad@smpn14sby.sch.id',
        'password' => Hash::make('password123'),
        'role_id' => $role->id,
        'status' => 'aktif'
    ]);

    $emp3 = Employee::create([
        'user_id' => $user3->id,
        'nip' => '197508201999031003',
        'nama' => 'Dr. Ahmad Wijaya, S.Pd., M.Pd.',
        'jabatan' => 'Guru IPA',
        'status' => 'aktif',
        'qr_value' => '197508201999031003'
    ]);

    echo "✅ Employee 3 created: " . $emp3->nama . "\n";

    echo "\n🎉 Total employees: " . Employee::count() . "\n";

} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}





