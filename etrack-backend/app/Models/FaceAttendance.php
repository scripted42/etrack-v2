<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;

class FaceAttendance extends Model
{
    protected $fillable = [
        'employee_id',
        'attendance_date',
        'attendance_time',
        'attendance_type',
        'photo_path',
        'confidence_score',
        'location',
        'ip_address',
        'user_agent',
        'status'
    ];

    protected $casts = [
        'attendance_date' => 'date',
        'attendance_time' => 'datetime:H:i:s',
        'confidence_score' => 'decimal:2',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    /**
     * Get the employee that owns the attendance
     */
    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    /**
     * Scope for filtering by date range
     */
    public function scopeDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('attendance_date', [$startDate, $endDate]);
    }

    /**
     * Scope for filtering by employee
     */
    public function scopeByEmployee($query, $employeeId)
    {
        return $query->where('employee_id', $employeeId);
    }

    /**
     * Scope for filtering by attendance type
     */
    public function scopeByType($query, $type)
    {
        return $query->where('attendance_type', $type);
    }

    /**
     * Scope for filtering by status
     */
    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Get attendance type label
     */
    public function getAttendanceTypeLabelAttribute()
    {
        $labels = [
            'early' => 'Datang Awal',
            'on_time' => 'Tepat Waktu',
            'late' => 'Terlambat',
            'overtime' => 'Lembur'
        ];

        return $labels[$this->attendance_type] ?? $this->attendance_type;
    }

    /**
     * Get status label
     */
    public function getStatusLabelAttribute()
    {
        $labels = [
            'present' => 'Hadir',
            'absent' => 'Tidak Hadir',
            'late' => 'Terlambat'
        ];

        return $labels[$this->status] ?? $this->status;
    }

    /**
     * Get confidence percentage
     */
    public function getConfidencePercentageAttribute()
    {
        return round($this->confidence_score * 100, 1);
    }

    /**
     * Get photo URL
     */
    public function getPhotoUrlAttribute()
    {
        if ($this->photo_path) {
            return Storage::disk('public')->url($this->photo_path);
        }
        return null;
    }

    /**
     * Check if attendance is late
     */
    public function isLate()
    {
        return $this->attendance_type === 'late';
    }

    /**
     * Check if attendance is early
     */
    public function isEarly()
    {
        return $this->attendance_type === 'early';
    }

    /**
     * Check if attendance is on time
     */
    public function isOnTime()
    {
        return $this->attendance_type === 'on_time';
    }

    /**
     * Get formatted attendance time
     */
    public function getFormattedTimeAttribute()
    {
        return Carbon::parse($this->attendance_time)->format('H:i');
    }

    /**
     * Get formatted attendance date
     */
    public function getFormattedDateAttribute()
    {
        return Carbon::parse($this->attendance_date)->format('d/m/Y');
    }
}
