<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use Illuminate\Support\Facades\Validator;

class UpdateUserEmail extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'user:update-email {user_id} {email}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update user email for MFA';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $userId = $this->argument('user_id');
        $newEmail = $this->argument('email');

        // Validate email
        $validator = Validator::make(['email' => $newEmail], [
            'email' => 'required|email|unique:users,email,' . $userId
        ]);

        if ($validator->fails()) {
            $this->error('Invalid email: ' . implode(', ', $validator->errors()->all()));
            return 1;
        }

        // Find user
        $user = User::find($userId);
        if (!$user) {
            $this->error("User with ID {$userId} not found!");
            return 1;
        }

        $oldEmail = $user->email;
        
        // Update email
        $user->update(['email' => $newEmail]);

        $this->info("✅ Email updated successfully!");
        $this->info("Old email: {$oldEmail}");
        $this->info("New email: {$newEmail}");
        $this->info("User: {$user->name} ({$user->username})");

        return 0;
    }
}
