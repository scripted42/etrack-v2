<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\BackupService;
use Illuminate\Support\Facades\Log;

class BackupCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'backup:create {type=auto : The type of backup (auto, daily, weekly, monthly)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a database backup';

    protected $backupService;

    /**
     * Create a new command instance.
     */
    public function __construct(BackupService $backupService)
    {
        parent::__construct();
        $this->backupService = $backupService;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $type = $this->argument('type');
        
        $this->info("Creating {$type} backup...");
        
        try {
            $result = $this->backupService->createBackup($type);
            
            if ($result['success']) {
                $this->info("✅ Backup created successfully!");
                $this->line("📁 File: {$result['filename']}");
                $this->line("📊 Size: {$result['size_formatted']}");
                $this->line("🗜️  Compressed: " . ($result['compressed'] ? 'Yes' : 'No'));
                $this->line("⏰ Created: {$result['created_at']}");
                
                Log::info("Scheduled backup completed successfully", [
                    'type' => $type,
                    'filename' => $result['filename'],
                    'size' => $result['size_formatted']
                ]);
                
                return 0;
            } else {
                $this->error("❌ Backup failed: {$result['error']}");
                
                Log::error("Scheduled backup failed", [
                    'type' => $type,
                    'error' => $result['error']
                ]);
                
                return 1;
            }
            
        } catch (\Exception $e) {
            $this->error("❌ Backup command failed: {$e->getMessage()}");
            
            Log::error("Backup command failed", [
                'type' => $type,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return 1;
        }
    }
}