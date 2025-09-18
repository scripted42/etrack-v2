<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;

class ListUsers extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'user:list';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'List all users with their email addresses';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $users = User::with('role')->get();

        $this->info('=== DAFTAR USER ===');
        $this->newLine();

        $headers = ['ID', 'Username', 'Name', 'Email', 'Role', 'MFA Status'];
        $rows = [];

        foreach ($users as $user) {
            $rows[] = [
                $user->id,
                $user->username,
                $user->name,
                $user->email,
                $user->role->name ?? 'No Role',
                $user->mfa_enabled ? '✅ Enabled' : '❌ Disabled'
            ];
        }

        $this->table($headers, $rows);
        
        $this->newLine();
        $this->info('Total users: ' . $users->count());
        $this->info('MFA enabled: ' . $users->where('mfa_enabled', true)->count());
    }
}
