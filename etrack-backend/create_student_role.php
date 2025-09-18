<?php

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Role;

try {
    // Check if student role exists
    $studentRole = Role::where('name', 'student')->first();
    
    if (!$studentRole) {
        // Create student role
        $studentRole = Role::create([
            'name' => 'student',
            'guard_name' => 'web',
            'created_at' => now(),
            'updated_at' => now()
        ]);
        echo "Student role created successfully.\n";
    } else {
        echo "Student role already exists.\n";
    }
    
    echo "Student role ID: " . $studentRole->id . "\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}



