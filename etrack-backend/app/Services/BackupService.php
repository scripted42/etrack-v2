<?php

namespace App\Services;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use Exception;

class BackupService
{
    protected $backupPath;
    protected $maxBackups;
    protected $compressionEnabled;

    public function __construct()
    {
        $this->backupPath = storage_path('app/backups');
        $this->maxBackups = config('backup.max_backups', 30);
        $this->compressionEnabled = config('backup.compression', true);
        
        // Ensure backup directory exists
        if (!file_exists($this->backupPath)) {
            mkdir($this->backupPath, 0755, true);
        }
    }

    /**
     * Create a full database backup
     */
    public function createBackup($type = 'manual'): array
    {
        try {
            $timestamp = Carbon::now()->format('Y-m-d_H-i-s');
            $filename = "backup_{$type}_{$timestamp}.sql";
            $filepath = $this->backupPath . '/' . $filename;

            // Get database configuration
            $dbConfig = config('database.connections.mysql');
            $host = $dbConfig['host'];
            $port = $dbConfig['port'];
            $database = $dbConfig['database'];
            $username = $dbConfig['username'];
            $password = $dbConfig['password'];

            // Create temporary MySQL config file to avoid password in command line
            $configFile = storage_path('app/temp_mysql_config.cnf');
            $configContent = sprintf(
                "[client]\nhost=%s\nport=%s\nuser=%s\npassword=%s\n",
                $host,
                $port,
                $username,
                $password
            );
            file_put_contents($configFile, $configContent);
            chmod($configFile, 0600); // Secure permissions

            // Build mysqldump command with config file
            $command = sprintf(
                'mysqldump --defaults-file=%s --single-transaction --routines --triggers %s > %s',
                escapeshellarg($configFile),
                escapeshellarg($database),
                escapeshellarg($filepath)
            );

            // Execute backup command
            $output = [];
            $returnCode = 0;
            exec($command, $output, $returnCode);

            // Clean up config file
            if (file_exists($configFile)) {
                unlink($configFile);
            }

            if ($returnCode !== 0) {
                throw new Exception("Backup command failed with return code: {$returnCode}");
            }

            // Check if file was created and has content
            if (!file_exists($filepath) || filesize($filepath) === 0) {
                throw new Exception("Backup file was not created or is empty");
            }

            // Compress backup if enabled
            $finalFilepath = $filepath;
            if ($this->compressionEnabled) {
                $finalFilepath = $this->compressBackup($filepath);
                unlink($filepath); // Remove uncompressed file
            }

            // Get file info
            $fileSize = filesize($finalFilepath);
            $fileSizeFormatted = $this->formatBytes($fileSize);

            // Log backup creation
            Log::info("Database backup created successfully", [
                'type' => $type,
                'filename' => basename($finalFilepath),
                'size' => $fileSizeFormatted,
                'path' => $finalFilepath
            ]);

            // Clean old backups
            $this->cleanOldBackups();

            return [
                'success' => true,
                'filename' => basename($finalFilepath),
                'filepath' => $finalFilepath,
                'size' => $fileSize,
                'size_formatted' => $fileSizeFormatted,
                'type' => $type,
                'created_at' => Carbon::now()->toISOString(),
                'compressed' => $this->compressionEnabled
            ];

        } catch (Exception $e) {
            Log::error("Database backup failed", [
                'type' => $type,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
                'type' => $type,
                'created_at' => Carbon::now()->toISOString()
            ];
        }
    }

    /**
     * Compress backup file using gzip
     */
    protected function compressBackup(string $filepath): string
    {
        $compressedFilepath = $filepath . '.gz';
        
        $fp_out = gzopen($compressedFilepath, 'wb9');
        $fp_in = fopen($filepath, 'rb');
        
        while (!feof($fp_in)) {
            gzwrite($fp_out, fread($fp_in, 1024 * 512));
        }
        
        fclose($fp_in);
        gzclose($fp_out);
        
        return $compressedFilepath;
    }

    /**
     * Restore database from backup
     */
    public function restoreBackup(string $filename): array
    {
        try {
            $filepath = $this->backupPath . '/' . $filename;
            
            if (!file_exists($filepath)) {
                throw new Exception("Backup file not found: {$filename}");
            }

            // Get database configuration
            $dbConfig = config('database.connections.mysql');
            $host = $dbConfig['host'];
            $port = $dbConfig['port'];
            $database = $dbConfig['database'];
            $username = $dbConfig['username'];
            $password = $dbConfig['password'];

            // Handle compressed files
            $tempFile = null;
            if (pathinfo($filepath, PATHINFO_EXTENSION) === 'gz') {
                $tempFile = $this->decompressBackup($filepath);
                $restoreFile = $tempFile;
            } else {
                $restoreFile = $filepath;
            }

            // Build mysql command for restore with full path
            $mysqlPath = 'C:\\Program Files\\MySQL\\MySQL Server 8.0\\bin\\mysql.exe';
            $command = sprintf(
                '"%s" --host=%s --port=%s --user=%s --password=%s %s < %s',
                $mysqlPath,
                escapeshellarg($host),
                escapeshellarg($port),
                escapeshellarg($username),
                escapeshellarg($password),
                escapeshellarg($database),
                escapeshellarg($restoreFile)
            );

            // Execute restore command with timeout
            $output = [];
            $returnCode = 0;
            
            // Use proc_open for better control and timeout
            $descriptorspec = [
                0 => ['pipe', 'r'],
                1 => ['pipe', 'w'],
                2 => ['pipe', 'w']
            ];
            
            $process = proc_open($command, $descriptorspec, $pipes);
            
            if (is_resource($process)) {
                // Close input pipe
                fclose($pipes[0]);
                
                // Set timeout (60 seconds)
                $timeout = 60;
                $startTime = time();
                
                while (time() - $startTime < $timeout) {
                    $status = proc_get_status($process);
                    if (!$status['running']) {
                        break;
                    }
                    usleep(100000); // Wait 100ms
                }
                
                // Get output
                $output = stream_get_contents($pipes[1]);
                $error = stream_get_contents($pipes[2]);
                
                fclose($pipes[1]);
                fclose($pipes[2]);
                
                $returnCode = proc_close($process);
                
                if ($error) {
                    Log::warning("MySQL restore stderr", ['error' => $error]);
                }
            } else {
                throw new Exception("Failed to start restore process");
            }

            // Clean up temp file if created
            if ($tempFile && file_exists($tempFile)) {
                unlink($tempFile);
            }

            if ($returnCode !== 0) {
                throw new Exception("Restore command failed with return code: {$returnCode}");
            }

            Log::info("Database restored successfully", [
                'filename' => $filename,
                'filepath' => $filepath
            ]);

            return [
                'success' => true,
                'filename' => $filename,
                'restored_at' => Carbon::now()->toISOString()
            ];

        } catch (Exception $e) {
            Log::error("Database restore failed", [
                'filename' => $filename,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
                'filename' => $filename,
                'restored_at' => Carbon::now()->toISOString()
            ];
        }
    }

    /**
     * Decompress backup file
     */
    protected function decompressBackup(string $filepath): string
    {
        $tempFile = tempnam(sys_get_temp_dir(), 'backup_restore_');
        
        $fp_in = gzopen($filepath, 'rb');
        $fp_out = fopen($tempFile, 'wb');
        
        while (!gzeof($fp_in)) {
            fwrite($fp_out, gzread($fp_in, 1024 * 512));
        }
        
        gzclose($fp_in);
        fclose($fp_out);
        
        return $tempFile;
    }

    /**
     * Get list of available backups
     */
    public function getBackups(): array
    {
        $backups = [];
        $files = glob($this->backupPath . '/backup_*.sql*');

        foreach ($files as $file) {
            $filename = basename($file);
            $fileSize = filesize($file);
            $fileTime = filemtime($file);
            
            // Parse backup info from filename
            preg_match('/backup_(.+)_(\d{4}-\d{2}-\d{2}_\d{2}-\d{2}-\d{2})\.sql(?:\.gz)?/', $filename, $matches);
            
            $backups[] = [
                'filename' => $filename,
                'type' => $matches[1] ?? 'unknown',
                'created_at' => Carbon::createFromFormat('Y-m-d_H-i-s', $matches[2] ?? '1970-01-01_00-00-00')->toISOString(),
                'size' => $fileSize,
                'size_formatted' => $this->formatBytes($fileSize),
                'compressed' => pathinfo($file, PATHINFO_EXTENSION) === 'gz',
                'filepath' => $file
            ];
        }

        // Sort by creation date (newest first)
        usort($backups, function ($a, $b) {
            return strtotime($b['created_at']) - strtotime($a['created_at']);
        });

        return $backups;
    }

    /**
     * Get backup statistics
     */
    public function getBackupStats(): array
    {
        $backups = $this->getBackups();
        $totalSize = array_sum(array_column($backups, 'size'));
        
        $stats = [
            'total_backups' => count($backups),
            'total_size' => $totalSize,
            'total_size_formatted' => $this->formatBytes($totalSize),
            'oldest_backup' => null,
            'newest_backup' => null,
            'backups_by_type' => [],
            'compression_enabled' => $this->compressionEnabled,
            'max_backups' => $this->maxBackups
        ];

        if (!empty($backups)) {
            $stats['oldest_backup'] = end($backups)['created_at'];
            $stats['newest_backup'] = $backups[0]['created_at'];
            
            // Count by type
            foreach ($backups as $backup) {
                $type = $backup['type'];
                $stats['backups_by_type'][$type] = ($stats['backups_by_type'][$type] ?? 0) + 1;
            }
        }

        return $stats;
    }

    /**
     * Delete a backup file
     */
    public function deleteBackup(string $filename): array
    {
        try {
            $filepath = $this->backupPath . '/' . $filename;
            
            if (!file_exists($filepath)) {
                throw new Exception("Backup file not found: {$filename}");
            }

            if (!unlink($filepath)) {
                throw new Exception("Failed to delete backup file: {$filename}");
            }

            Log::info("Backup file deleted", [
                'filename' => $filename,
                'filepath' => $filepath
            ]);

            return [
                'success' => true,
                'filename' => $filename,
                'deleted_at' => Carbon::now()->toISOString()
            ];

        } catch (Exception $e) {
            Log::error("Failed to delete backup", [
                'filename' => $filename,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
                'filename' => $filename
            ];
        }
    }

    /**
     * Download backup file
     */
    public function downloadBackup(string $filename): array
    {
        try {
            $filepath = $this->backupPath . '/' . $filename;
            
            if (!file_exists($filepath)) {
                throw new Exception("Backup file not found: {$filename}");
            }

            return [
                'success' => true,
                'filepath' => $filepath,
                'filename' => $filename,
                'size' => filesize($filepath),
                'mime_type' => pathinfo($filepath, PATHINFO_EXTENSION) === 'gz' ? 'application/gzip' : 'application/sql'
            ];

        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'filename' => $filename
            ];
        }
    }

    /**
     * Clean old backups based on max_backups setting
     */
    protected function cleanOldBackups(): void
    {
        $backups = $this->getBackups();
        
        if (count($backups) > $this->maxBackups) {
            $backupsToDelete = array_slice($backups, $this->maxBackups);
            
            foreach ($backupsToDelete as $backup) {
                $this->deleteBackup($backup['filename']);
            }
            
            Log::info("Cleaned old backups", [
                'deleted_count' => count($backupsToDelete),
                'max_backups' => $this->maxBackups
            ]);
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

    /**
     * Test backup functionality
     */
    public function testBackup(): array
    {
        try {
            // Test database connection
            $dbConfig = config('database.connections.mysql');
            $host = $dbConfig['host'];
            $port = $dbConfig['port'];
            $database = $dbConfig['database'];
            $username = $dbConfig['username'];
            $password = $dbConfig['password'];

            // Create temporary MySQL config file for test
            $configFile = storage_path('app/temp_mysql_test_config.cnf');
            $configContent = sprintf(
                "[client]\nhost=%s\nport=%s\nuser=%s\npassword=%s\n",
                $host,
                $port,
                $username,
                $password
            );
            file_put_contents($configFile, $configContent);
            chmod($configFile, 0600); // Secure permissions

            // Test mysqldump command with config file
            $command = sprintf(
                'mysqldump --defaults-file=%s --single-transaction --no-data %s',
                escapeshellarg($configFile),
                escapeshellarg($database)
            );

            $output = [];
            $returnCode = 0;
            exec($command, $output, $returnCode);

            // Clean up config file
            if (file_exists($configFile)) {
                unlink($configFile);
            }

            if ($returnCode !== 0) {
                throw new Exception("mysqldump test failed with return code: {$returnCode}");
            }

            return [
                'success' => true,
                'message' => 'Backup system is working correctly',
                'tested_at' => Carbon::now()->toISOString()
            ];

        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'tested_at' => Carbon::now()->toISOString()
            ];
        }
    }
}
