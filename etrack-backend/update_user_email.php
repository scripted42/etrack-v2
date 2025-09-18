<?php

require_once 'vendor/autoload.php';

use Illuminate\Support\Facades\DB;

// Bootstrap Laravel
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== UPDATE USER EMAIL ===\n";
echo "Current users:\n";

// Get all users
$users = DB::table('users')->select('id', 'username', 'name', 'email')->get();

foreach ($users as $user) {
    echo "ID: {$user->id} | Username: {$user->username} | Name: {$user->name} | Email: {$user->email}\n";
}

echo "\nEnter user ID to update email: ";
$userId = trim(fgets(STDIN));

echo "Enter new email: ";
$newEmail = trim(fgets(STDIN));

// Validate email
if (!filter_var($newEmail, FILTER_VALIDATE_EMAIL)) {
    echo "❌ Invalid email format!\n";
    exit(1);
}

// Check if email already exists
$existingUser = DB::table('users')->where('email', $newEmail)->where('id', '!=', $userId)->first();
if ($existingUser) {
    echo "❌ Email already exists for another user!\n";
    exit(1);
}

// Update email
$updated = DB::table('users')->where('id', $userId)->update(['email' => $newEmail]);

if ($updated) {
    echo "✅ Email updated successfully!\n";
    echo "New email: {$newEmail}\n";
} else {
    echo "❌ Failed to update email!\n";
}
