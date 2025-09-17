<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\BackupService;
use App\Services\AuditService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class BackupController extends Controller
{
    protected $backupService;

    public function __construct(BackupService $backupService)
    {
        $this->backupService = $backupService;
    }

    /**
     * Get list of available backups
     */
    public function index(Request $request)
    {
        try {
            $backups = $this->backupService->getBackups();
            $stats = $this->backupService->getBackupStats();

            return response()->json([
                'success' => true,
                'data' => [
                    'backups' => $backups,
                    'statistics' => $stats
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to get backups list', [
                'error' => $e->getMessage(),
                'user_id' => Auth::id()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve backups',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Create a new backup
     */
    public function create(Request $request)
    {
        try {
            $type = $request->input('type', 'manual');
            $user = Auth::user();

            // Log backup creation attempt
            AuditService::log('BACKUP_CREATE', [
                'type' => $type,
                'user_id' => $user->id,
                'username' => $user->username
            ], $user, $request);

            $result = $this->backupService->createBackup($type);

            if ($result['success']) {
                // Log successful backup
                AuditService::log('BACKUP_CREATED', [
                    'filename' => $result['filename'],
                    'size' => $result['size_formatted'],
                    'type' => $type,
                    'compressed' => $result['compressed']
                ], $user, $request);

                return response()->json([
                    'success' => true,
                    'message' => 'Backup created successfully',
                    'data' => $result
                ]);
            } else {
                // Log failed backup
                AuditService::log('BACKUP_FAILED', [
                    'type' => $type,
                    'error' => $result['error']
                ], $user, $request);

                return response()->json([
                    'success' => false,
                    'message' => 'Failed to create backup',
                    'error' => $result['error']
                ], 500);
            }

        } catch (\Exception $e) {
            Log::error('Backup creation failed', [
                'error' => $e->getMessage(),
                'user_id' => Auth::id(),
                'type' => $request->input('type', 'manual')
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Backup creation failed',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Restore from backup
     */
    public function restore(Request $request, string $filename)
    {
        try {
            $user = Auth::user();

            // Log restore attempt
            AuditService::log('BACKUP_RESTORE', [
                'filename' => $filename,
                'user_id' => $user->id,
                'username' => $user->username
            ], $user, $request);

            $result = $this->backupService->restoreBackup($filename);

            if ($result['success']) {
                // Log successful restore
                AuditService::log('BACKUP_RESTORED', [
                    'filename' => $filename,
                    'restored_at' => $result['restored_at']
                ], $user, $request);

                return response()->json([
                    'success' => true,
                    'message' => 'Database restored successfully',
                    'data' => $result
                ]);
            } else {
                // Log failed restore
                AuditService::log('BACKUP_RESTORE_FAILED', [
                    'filename' => $filename,
                    'error' => $result['error']
                ], $user, $request);

                return response()->json([
                    'success' => false,
                    'message' => 'Failed to restore database',
                    'error' => $result['error']
                ], 500);
            }

        } catch (\Exception $e) {
            Log::error('Backup restore failed', [
                'error' => $e->getMessage(),
                'user_id' => Auth::id(),
                'filename' => $filename
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Backup restore failed',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Download backup file
     */
    public function download(string $filename)
    {
        try {
            $user = Auth::user();

            // Log download attempt
            AuditService::log('BACKUP_DOWNLOAD', [
                'filename' => $filename,
                'user_id' => $user->id,
                'username' => $user->username
            ], $user, request());

            $result = $this->backupService->downloadBackup($filename);

            if ($result['success']) {
                // Log successful download
                AuditService::log('BACKUP_DOWNLOADED', [
                    'filename' => $filename,
                    'size' => $result['size']
                ], $user, request());

                return response()->download(
                    $result['filepath'],
                    $filename,
                    [
                        'Content-Type' => $result['mime_type'],
                        'Content-Disposition' => 'attachment; filename="' . $filename . '"'
                    ]
                );
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Backup file not found',
                    'error' => $result['error']
                ], 404);
            }

        } catch (\Exception $e) {
            Log::error('Backup download failed', [
                'error' => $e->getMessage(),
                'user_id' => Auth::id(),
                'filename' => $filename
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Backup download failed',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Delete backup file
     */
    public function destroy(string $filename)
    {
        try {
            $user = Auth::user();

            // Log delete attempt
            AuditService::log('BACKUP_DELETE', [
                'filename' => $filename,
                'user_id' => $user->id,
                'username' => $user->username
            ], $user, request());

            $result = $this->backupService->deleteBackup($filename);

            if ($result['success']) {
                // Log successful delete
                AuditService::log('BACKUP_DELETED', [
                    'filename' => $filename,
                    'deleted_at' => $result['deleted_at']
                ], $user, request());

                return response()->json([
                    'success' => true,
                    'message' => 'Backup deleted successfully',
                    'data' => $result
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to delete backup',
                    'error' => $result['error']
                ], 500);
            }

        } catch (\Exception $e) {
            Log::error('Backup deletion failed', [
                'error' => $e->getMessage(),
                'user_id' => Auth::id(),
                'filename' => $filename
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Backup deletion failed',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get backup statistics
     */
    public function statistics()
    {
        try {
            $stats = $this->backupService->getBackupStats();

            return response()->json([
                'success' => true,
                'data' => $stats
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to get backup statistics', [
                'error' => $e->getMessage(),
                'user_id' => Auth::id()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve backup statistics',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Test backup system
     */
    public function test()
    {
        try {
            $user = Auth::user();

            // Log test attempt
            AuditService::log('BACKUP_TEST', [
                'user_id' => $user->id,
                'username' => $user->username
            ], $user, request());

            $result = $this->backupService->testBackup();

            if ($result['success']) {
                // Log successful test
                AuditService::log('BACKUP_TEST_SUCCESS', [
                    'tested_at' => $result['tested_at']
                ], $user, request());

                return response()->json([
                    'success' => true,
                    'message' => 'Backup system test passed',
                    'data' => $result
                ]);
            } else {
                // Log failed test
                AuditService::log('BACKUP_TEST_FAILED', [
                    'error' => $result['error']
                ], $user, request());

                return response()->json([
                    'success' => false,
                    'message' => 'Backup system test failed',
                    'error' => $result['error']
                ], 500);
            }

        } catch (\Exception $e) {
            Log::error('Backup system test failed', [
                'error' => $e->getMessage(),
                'user_id' => Auth::id()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Backup system test failed',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get backup configuration
     */
    public function config()
    {
        try {
            $config = [
                'max_backups' => config('backup.max_backups', 30),
                'compression_enabled' => config('backup.compression', true),
                'backup_path' => config('backup.path', storage_path('app/backups')),
                'auto_backup_enabled' => config('backup.auto_backup', false),
                'backup_schedule' => config('backup.schedule', 'daily')
            ];

            return response()->json([
                'success' => true,
                'data' => $config
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to get backup configuration', [
                'error' => $e->getMessage(),
                'user_id' => Auth::id()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve backup configuration',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}