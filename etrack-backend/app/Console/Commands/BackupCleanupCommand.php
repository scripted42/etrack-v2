<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\BackupService;
use Illuminate\Support\Facades\Log;

class BackupCleanupCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'backup:cleanup {--dry-run : Show what would be deleted without actually deleting}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clean up old backup files based on retention policy';

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
        $dryRun = $this->option('dry-run');
        
        if ($dryRun) {
            $this->info("🔍 Running backup cleanup in DRY RUN mode...");
        } else {
            $this->info("🧹 Running backup cleanup...");
        }
        
        try {
            $backups = $this->backupService->getBackups();
            $maxBackups = config('backup.max_backups', 30);
            
            $this->line("📊 Total backups: " . count($backups));
            $this->line("📋 Max backups allowed: {$maxBackups}");
            
            if (count($backups) <= $maxBackups) {
                $this->info("✅ No cleanup needed. Backup count is within limits.");
                return 0;
            }
            
            $backupsToDelete = array_slice($backups, $maxBackups);
            $deletedCount = 0;
            $freedSpace = 0;
            
            $this->line("🗑️  Backups to delete: " . count($backupsToDelete));
            
            foreach ($backupsToDelete as $backup) {
                $this->line("   - {$backup['filename']} ({$backup['size_formatted']}) - {$backup['created_at']}");
                
                if (!$dryRun) {
                    $result = $this->backupService->deleteBackup($backup['filename']);
                    if ($result['success']) {
                        $deletedCount++;
                        $freedSpace += $backup['size'];
                    } else {
                        $this->error("   ❌ Failed to delete: {$result['error']}");
                    }
                }
            }
            
            if ($dryRun) {
                $this->info("🔍 DRY RUN completed. No files were actually deleted.");
                $this->line("💾 Space that would be freed: " . $this->formatBytes($freedSpace));
            } else {
                $this->info("✅ Cleanup completed!");
                $this->line("🗑️  Files deleted: {$deletedCount}");
                $this->line("💾 Space freed: " . $this->formatBytes($freedSpace));
                
                Log::info("Backup cleanup completed", [
                    'deleted_count' => $deletedCount,
                    'freed_space' => $freedSpace,
                    'max_backups' => $maxBackups
                ]);
            }
            
            return 0;
            
        } catch (\Exception $e) {
            $this->error("❌ Cleanup failed: {$e->getMessage()}");
            
            Log::error("Backup cleanup failed", [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return 1;
        }
    }

    /**
     * Format bytes to human readable format
     */
    protected function formatBytes(int $bytes, int $precision = 2): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        
        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }
        
        return round($bytes, $precision) . ' ' . $units[$i];
    }
}