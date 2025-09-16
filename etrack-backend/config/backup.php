<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Backup Configuration
    |--------------------------------------------------------------------------
    |
    | This file contains the configuration for the backup system.
    |
    */

    /*
    |--------------------------------------------------------------------------
    | Backup Path
    |--------------------------------------------------------------------------
    |
    | The directory where backup files will be stored.
    |
    */
    'path' => storage_path('app/backups'),

    /*
    |--------------------------------------------------------------------------
    | Maximum Backups
    |--------------------------------------------------------------------------
    |
    | The maximum number of backup files to keep. Older backups will be
    | automatically deleted when this limit is exceeded.
    |
    */
    'max_backups' => env('BACKUP_MAX_FILES', 30),

    /*
    |--------------------------------------------------------------------------
    | Compression
    |--------------------------------------------------------------------------
    |
    | Whether to compress backup files using gzip. This can significantly
    | reduce file size but may take longer to create and restore.
    |
    */
    'compression' => env('BACKUP_COMPRESSION', true),

    /*
    |--------------------------------------------------------------------------
    | Auto Backup
    |--------------------------------------------------------------------------
    |
    | Whether to enable automatic scheduled backups.
    |
    */
    'auto_backup' => env('BACKUP_AUTO_ENABLED', true),

    /*
    |--------------------------------------------------------------------------
    | Backup Schedule
    |--------------------------------------------------------------------------
    |
    | The schedule for automatic backups. Options: daily, weekly, monthly
    |
    */
    'schedule' => env('BACKUP_SCHEDULE', 'daily'),

    /*
    |--------------------------------------------------------------------------
    | Backup Time
    |--------------------------------------------------------------------------
    |
    | The time when automatic backups should be created (24-hour format).
    |
    */
    'backup_time' => env('BACKUP_TIME', '02:00'),

    /*
    |--------------------------------------------------------------------------
    | Notification
    |--------------------------------------------------------------------------
    |
    | Whether to send notifications when backups are created or fail.
    |
    */
    'notifications' => [
        'enabled' => env('BACKUP_NOTIFICATIONS', false),
        'email' => env('BACKUP_NOTIFICATION_EMAIL'),
        'slack' => env('BACKUP_SLACK_WEBHOOK'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Backup Types
    |--------------------------------------------------------------------------
    |
    | Different types of backups and their retention policies.
    |
    */
    'types' => [
        'manual' => [
            'retention_days' => 90,
            'max_files' => 10,
        ],
        'auto' => [
            'retention_days' => 30,
            'max_files' => 30,
        ],
        'daily' => [
            'retention_days' => 30,
            'max_files' => 30,
        ],
        'weekly' => [
            'retention_days' => 90,
            'max_files' => 12,
        ],
        'monthly' => [
            'retention_days' => 365,
            'max_files' => 12,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Database Configuration
    |--------------------------------------------------------------------------
    |
    | Database-specific backup settings.
    |
    */
    'database' => [
        'include_data' => true,
        'include_structure' => true,
        'include_routines' => true,
        'include_triggers' => true,
        'single_transaction' => true,
        'lock_tables' => false,
        'add_drop_table' => true,
        'add_drop_database' => false,
        'disable_keys' => true,
        'extended_insert' => true,
        'complete_insert' => false,
        'hex_blob' => false,
        'default_character_set' => 'utf8mb4',
    ],

    /*
    |--------------------------------------------------------------------------
    | Security
    |--------------------------------------------------------------------------
    |
    | Security settings for backup files.
    |
    */
    'security' => [
        'encrypt' => env('BACKUP_ENCRYPT', false),
        'encryption_key' => env('BACKUP_ENCRYPTION_KEY'),
        'file_permissions' => 0600,
        'directory_permissions' => 0700,
    ],

    /*
    |--------------------------------------------------------------------------
    | Remote Storage
    |--------------------------------------------------------------------------
    |
    | Configuration for storing backups in remote locations.
    |
    */
    'remote' => [
        'enabled' => env('BACKUP_REMOTE_ENABLED', false),
        'driver' => env('BACKUP_REMOTE_DRIVER', 's3'), // s3, ftp, sftp
        'config' => [
            's3' => [
                'bucket' => env('BACKUP_S3_BUCKET'),
                'region' => env('BACKUP_S3_REGION'),
                'key' => env('BACKUP_S3_KEY'),
                'secret' => env('BACKUP_S3_SECRET'),
                'path' => env('BACKUP_S3_PATH', 'backups/'),
            ],
            'ftp' => [
                'host' => env('BACKUP_FTP_HOST'),
                'port' => env('BACKUP_FTP_PORT', 21),
                'username' => env('BACKUP_FTP_USERNAME'),
                'password' => env('BACKUP_FTP_PASSWORD'),
                'path' => env('BACKUP_FTP_PATH', '/backups/'),
                'passive' => env('BACKUP_FTP_PASSIVE', true),
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Health Check
    |--------------------------------------------------------------------------
    |
    | Configuration for backup health monitoring.
    |
    */
    'health_check' => [
        'enabled' => env('BACKUP_HEALTH_CHECK', true),
        'max_age_hours' => env('BACKUP_MAX_AGE_HOURS', 25), // Alert if no backup in 25 hours
        'min_size_mb' => env('BACKUP_MIN_SIZE_MB', 1), // Alert if backup is smaller than 1MB
        'check_interval_hours' => env('BACKUP_CHECK_INTERVAL', 6),
    ],
];
