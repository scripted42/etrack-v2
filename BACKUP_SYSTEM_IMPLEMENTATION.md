# Backup System Implementation

## Overview
Sistem backup otomatis MySQL yang komprehensif untuk aplikasi Etrack. Sistem ini menyediakan backup manual dan otomatis, restore, download, dan manajemen file backup.

## Features

### 🔧 Core Features
- **Manual Backup**: Buat backup database secara manual
- **Automatic Backup**: Backup terjadwal (daily, weekly, monthly)
- **Restore**: Restore database dari backup file
- **Download**: Download backup file untuk penyimpanan eksternal
- **Delete**: Hapus backup file yang tidak diperlukan
- **Compression**: Kompresi backup file untuk menghemat ruang
- **Auto Cleanup**: Hapus backup lama secara otomatis

### 📊 Monitoring & Statistics
- **Backup Statistics**: Statistik lengkap backup files
- **Health Check**: Monitoring kesehatan sistem backup
- **File Management**: Manajemen file backup dengan metadata
- **Audit Trail**: Logging semua aktivitas backup

### 🛡️ Security & Reliability
- **Authentication**: Semua operasi backup memerlukan autentikasi
- **Audit Logging**: Semua aktivitas backup dicatat dalam audit trail
- **Error Handling**: Penanganan error yang robust
- **File Permissions**: Keamanan file backup

## Technical Implementation

### Backend Components

#### 1. BackupService (`app/Services/BackupService.php`)
```php
class BackupService
{
    // Core backup functionality
    public function createBackup($type = 'manual'): array
    public function restoreBackup(string $filename): array
    public function getBackups(): array
    public function getBackupStats(): array
    public function deleteBackup(string $filename): array
    public function downloadBackup(string $filename): array
    public function testBackup(): array
    
    // Utility methods
    protected function compressBackup(string $filepath): string
    protected function decompressBackup(string $filepath): string
    protected function cleanOldBackups(): void
    protected function formatBytes(int $bytes, int $precision = 2): string
}
```

#### 2. BackupController (`app/Http/Controllers/Api/BackupController.php`)
```php
class BackupController extends Controller
{
    // API endpoints
    public function index(Request $request)           // GET /api/backups
    public function create(Request $request)          // POST /api/backups
    public function restore(Request $request, string $filename) // POST /api/backups/{filename}/restore
    public function download(string $filename)        // GET /api/backups/{filename}/download
    public function destroy(string $filename)         // DELETE /api/backups/{filename}
    public function statistics()                      // GET /api/backups/statistics
    public function config()                          // GET /api/backups/config
    public function test()                            // POST /api/backups/test
}
```

#### 3. Console Commands
```php
// Create backup command
php artisan backup:create {type}  // type: auto, daily, weekly, monthly

// Cleanup command
php artisan backup:cleanup {--dry-run}
```

#### 4. Scheduled Tasks (`app/Console/Kernel.php`)
```php
protected function schedule(Schedule $schedule): void
{
    // Daily backup at 2:00 AM
    $schedule->command('backup:create daily')->dailyAt('02:00');
    
    // Weekly backup on Sunday at 3:00 AM
    $schedule->command('backup:create weekly')->weeklyOn(0, '03:00');
    
    // Monthly backup on the 1st at 4:00 AM
    $schedule->command('backup:create monthly')->monthlyOn(1, '04:00');
    
    // Cleanup old backups daily at 5:00 AM
    $schedule->command('backup:cleanup')->dailyAt('05:00');
}
```

### Frontend Components

#### 1. Backup Service (`src/services/backup.ts`)
```typescript
// API functions
export async function fetchBackups(): Promise<BackupResponse>
export async function createBackup(type: string): Promise<BackupCreateResponse>
export async function fetchBackupStatistics(): Promise<{ success: boolean; data: BackupStatistics }>
export async function restoreBackup(filename: string): Promise<{ success: boolean; message: string; data: any }>
export async function downloadBackup(filename: string): Promise<Blob>
export async function deleteBackup(filename: string): Promise<{ success: boolean; message: string; data: any }>

// Helper functions
export function formatBackupType(type: string): string
export function getBackupTypeColor(type: string): string
export function getBackupTypeIcon(type: string): string
export function formatFileSize(bytes: number): string
export function formatBackupDate(dateString: string): string
export function getBackupAge(dateString: string): string
export function getBackupStatus(dateString: string): { status: string; color: string }
```

#### 2. Backup Dashboard (`src/views/BackupDashboard.vue`)
- **Quick Stats**: Total backups, total size, last backup, max backups
- **Action Buttons**: Create backup, test system, refresh data, configuration
- **Backup List**: Table dengan semua backup files
- **Statistics**: Backup by type, system information
- **Dialogs**: Restore confirmation, delete confirmation, configuration

### Configuration

#### 1. Backup Config (`config/backup.php`)
```php
return [
    'path' => storage_path('app/backups'),
    'max_backups' => env('BACKUP_MAX_FILES', 30),
    'compression' => env('BACKUP_COMPRESSION', true),
    'auto_backup' => env('BACKUP_AUTO_ENABLED', true),
    'schedule' => env('BACKUP_SCHEDULE', 'daily'),
    'backup_time' => env('BACKUP_TIME', '02:00'),
    'notifications' => [
        'enabled' => env('BACKUP_NOTIFICATIONS', false),
        'email' => env('BACKUP_NOTIFICATION_EMAIL'),
    ],
    'types' => [
        'manual' => ['retention_days' => 90, 'max_files' => 10],
        'auto' => ['retention_days' => 30, 'max_files' => 30],
        'daily' => ['retention_days' => 30, 'max_files' => 30],
        'weekly' => ['retention_days' => 90, 'max_files' => 12],
        'monthly' => ['retention_days' => 365, 'max_files' => 12],
    ],
    'database' => [
        'include_data' => true,
        'include_structure' => true,
        'include_routines' => true,
        'include_triggers' => true,
        'single_transaction' => true,
    ],
    'security' => [
        'encrypt' => env('BACKUP_ENCRYPT', false),
        'file_permissions' => 0600,
        'directory_permissions' => 0700,
    ],
];
```

#### 2. Environment Variables
```env
# Backup Configuration
BACKUP_MAX_FILES=30
BACKUP_COMPRESSION=true
BACKUP_AUTO_ENABLED=true
BACKUP_SCHEDULE=daily
BACKUP_TIME=02:00
BACKUP_NOTIFICATIONS=false
BACKUP_NOTIFICATION_EMAIL=
BACKUP_ENCRYPT=false
BACKUP_ENCRYPTION_KEY=
```

## API Endpoints

### Backup Management
```
GET    /api/backups                    # List all backups
POST   /api/backups                    # Create new backup
GET    /api/backups/statistics         # Get backup statistics
GET    /api/backups/config             # Get backup configuration
POST   /api/backups/test               # Test backup system
POST   /api/backups/{filename}/restore # Restore from backup
GET    /api/backups/{filename}/download # Download backup file
DELETE /api/backups/{filename}         # Delete backup file
```

### Request/Response Examples

#### Create Backup
```bash
POST /api/backups
Content-Type: application/json
Authorization: Bearer {token}

{
  "type": "manual"
}
```

Response:
```json
{
  "success": true,
  "message": "Backup created successfully",
  "data": {
    "filename": "backup_manual_2025-01-15_14-30-25.sql.gz",
    "filepath": "/path/to/backup_manual_2025-01-15_14-30-25.sql.gz",
    "size": 1048576,
    "size_formatted": "1.00 MB",
    "type": "manual",
    "created_at": "2025-01-15T14:30:25.000000Z",
    "compressed": true
  }
}
```

#### List Backups
```bash
GET /api/backups
Authorization: Bearer {token}
```

Response:
```json
{
  "success": true,
  "data": {
    "backups": [
      {
        "filename": "backup_manual_2025-01-15_14-30-25.sql.gz",
        "type": "manual",
        "created_at": "2025-01-15T14:30:25.000000Z",
        "size": 1048576,
        "size_formatted": "1.00 MB",
        "compressed": true,
        "filepath": "/path/to/backup_manual_2025-01-15_14-30-25.sql.gz"
      }
    ],
    "statistics": {
      "total_backups": 5,
      "total_size": 5242880,
      "total_size_formatted": "5.00 MB",
      "oldest_backup": "2025-01-10T02:00:00.000000Z",
      "newest_backup": "2025-01-15T14:30:25.000000Z",
      "backups_by_type": {
        "manual": 2,
        "daily": 3
      },
      "compression_enabled": true,
      "max_backups": 30
    }
  }
}
```

## Usage

### Manual Backup
```bash
# Create manual backup
php artisan backup:create manual

# Create daily backup
php artisan backup:create daily
```

### Cleanup
```bash
# Cleanup old backups
php artisan backup:cleanup

# Dry run (show what would be deleted)
php artisan backup:cleanup --dry-run
```

### Scheduled Tasks
```bash
# Start scheduler (for production)
php artisan schedule:work

# Run scheduled tasks manually
php artisan schedule:run
```

## Security Considerations

### 1. File Permissions
- Backup files: 0600 (read/write for owner only)
- Backup directory: 0700 (access for owner only)

### 2. Authentication
- All backup operations require authentication
- API endpoints protected by `auth:sanctum` middleware

### 3. Audit Trail
- All backup operations logged in audit trail
- Includes user, action, timestamp, and details

### 4. Error Handling
- Comprehensive error handling and logging
- No sensitive information exposed in error messages

## Monitoring & Maintenance

### 1. Health Checks
- Test backup system functionality
- Monitor backup file sizes and ages
- Alert on backup failures

### 2. Logging
- All backup operations logged
- Error logs for troubleshooting
- Performance metrics

### 3. Cleanup
- Automatic cleanup of old backups
- Configurable retention policies
- Manual cleanup options

## Troubleshooting

### Common Issues

#### 1. Backup Creation Fails
```bash
# Check mysqldump availability
which mysqldump

# Test database connection
php artisan tinker
>>> DB::connection()->getPdo();

# Check backup directory permissions
ls -la storage/app/backups/
```

#### 2. Restore Fails
```bash
# Check backup file exists
ls -la storage/app/backups/backup_*.sql*

# Test restore with dry run
mysql --host=localhost --user=root --password=password --database=test < backup_file.sql
```

#### 3. Scheduled Tasks Not Running
```bash
# Check if scheduler is running
php artisan schedule:list

# Test scheduled tasks
php artisan schedule:run

# Check cron job
crontab -l
```

### Log Files
- Laravel logs: `storage/logs/laravel.log`
- Backup specific logs: Check Laravel log for backup operations
- System logs: `/var/log/syslog` (Linux) or Event Viewer (Windows)

## Performance Considerations

### 1. Backup Size
- Use compression to reduce file size
- Consider excluding large tables if not critical
- Monitor disk space usage

### 2. Backup Frequency
- Balance between data safety and resource usage
- Consider incremental backups for large databases
- Monitor backup duration and system impact

### 3. Storage
- Use fast storage for backup directory
- Consider remote storage for long-term retention
- Monitor disk I/O during backup operations

## Future Enhancements

### 1. Remote Storage
- S3 integration
- FTP/SFTP support
- Cloud storage providers

### 2. Encryption
- Backup file encryption
- Key management
- Secure key storage

### 3. Incremental Backups
- Binary log based incremental backups
- Point-in-time recovery
- Reduced storage requirements

### 4. Notifications
- Email notifications
- Slack integration
- Webhook support

### 5. Monitoring
- Backup health dashboard
- Performance metrics
- Alert system

## Conclusion

Sistem backup yang komprehensif ini menyediakan:
- ✅ Backup manual dan otomatis
- ✅ Restore functionality
- ✅ File management
- ✅ Security dan audit trail
- ✅ Monitoring dan statistics
- ✅ User-friendly interface
- ✅ Scheduled tasks
- ✅ Error handling
- ✅ Documentation

Sistem ini siap untuk production dan dapat diandalkan untuk menjaga keamanan data database MySQL.
