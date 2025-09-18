<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Attendance System Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for the attendance system including GPS verification,
    | QR code settings, and school location coordinates.
    |
    */

    // School Location Coordinates
    'school_latitude' => -7.2374924571658195,
    'school_longitude' => 112.62761534309656,
    'school_radius_km' => 0.5, // 500 meters radius
    
    // Work Schedule - Different for Teachers and Staff
    'work_start_time' => '07:00',  // Default jam masuk
    'work_end_time' => '14:30',   // Jam pulang untuk semua
    'late_threshold_minutes' => 1, // Late if check-in after 1 minute
    
    // Teacher Schedule
    'teacher_start_time' => '06:30',  // Guru maksimal 06:30
    'teacher_late_threshold' => '06:31', // Guru terlambat setelah 06:31
    
    // Staff Schedule  
    'staff_start_time' => '07:00',     // Pegawai maksimal 07:00
    'staff_late_threshold' => '07:01', // Pegawai terlambat setelah 07:01
    
    // QR Code Settings
    'qr_code_expiry_seconds' => 10,
    'qr_code_auto_refresh' => true,
    
    // GPS Settings
    'gps_accuracy_threshold' => 100, // meters
    'gps_timeout_seconds' => 30,
    
    // Photo Settings
    'selfie_max_size_mb' => 5,
    'selfie_allowed_formats' => ['jpg', 'jpeg', 'png'],
    'selfie_quality' => 0.8,
    
    // Attendance Rules
    'half_day_threshold_hours' => 4, // Half day if less than 4 hours
    'auto_checkout_hours' => 8, // Auto checkout after 8 hours
    
    // Notification Settings
    'send_notifications' => true,
    'notification_email' => 'admin@sekolah.com',
    
    // Security Settings
    'max_attempts_per_day' => 3,
    'block_duration_minutes' => 30,
    'require_device_verification' => true,
    
    // Backup Settings
    'backup_attendance_data' => true,
    'backup_frequency_days' => 7,
    
    // Integration Settings
    'enable_api_access' => true,
    'api_rate_limit' => 60, // requests per minute
    'webhook_url' => null, // For external integrations
    
    // Display Settings
    'timezone' => 'Asia/Jakarta',
    'date_format' => 'd/m/Y',
    'time_format' => 'H:i:s',
    
    // Mobile App Settings
    'mobile_app_required' => false,
    'allow_web_attendance' => true,
    'require_camera_permission' => true,
    'require_location_permission' => true,
];
