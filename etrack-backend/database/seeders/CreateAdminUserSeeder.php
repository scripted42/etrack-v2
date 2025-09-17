<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class CreateAdminUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Ensure Admin role exists
        $adminRole = Role::firstOrCreate(
            ['name' => 'Admin'],
            ['description' => 'Administrator sistem dengan akses penuh']
        );

        // Create a new admin user if not exists
        User::firstOrCreate(
            ['username' => 'admin'],
            [
                'name' => 'Administrator',
                'email' => 'admin@smpn14sby.sch.id',
                'password' => Hash::make('admin123'),
                'role_id' => $adminRole->id,
                'status' => 'aktif',
            ]
        );
    }
}


