<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Attendance extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'type',
        'attendance_date',
        'check_in_time',
        'check_out_time',
        'status',
        'notes'
    ];

    protected $casts = [
        'attendance_date' => 'date',
        'check_in_time' => 'datetime:H:i:s',
        'check_out_time' => 'datetime:H:i:s',
    ];

    /**
     * Get the user that owns the attendance
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the attendance verification
     */
    public function verification(): HasOne
    {
        return $this->hasOne(AttendanceVerification::class);
    }

    /**
     * Check if attendance is verified with all 3 key factors
     */
    public function isVerified(): bool
    {
        return $this->verification !== null;
    }

    /**
     * Get attendance status color
     */
    public function getStatusColorAttribute(): string
    {
        return match($this->status) {
            'present' => 'success',
            'late' => 'warning',
            'absent' => 'error',
            'half_day' => 'info',
            default => 'grey'
        };
    }

    /**
     * Get attendance status icon
     */
    public function getStatusIconAttribute(): string
    {
        return match($this->status) {
            'present' => 'mdi-check-circle',
            'late' => 'mdi-clock-alert',
            'absent' => 'mdi-close-circle',
            'half_day' => 'mdi-clock-half',
            default => 'mdi-help-circle'
        };
    }
}
