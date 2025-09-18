<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

class Employee extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'nip',
        'nama',
        'jabatan',
        'status',
        'photo_path',
        'qr_value',
        'face_photo_path',
        'face_descriptor',
        'face_registered_at',
    ];

    /**
     * Get the user that owns the employee.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the employee's identity.
     */
    public function identity()
    {
        return $this->hasOne(EmployeeIdentity::class);
    }

    /**
     * Get the employee's contact.
     */
    public function contact()
    {
        return $this->hasOne(EmployeeContact::class);
    }

    /**
     * Get the employee's families.
     */
    public function families()
    {
        return $this->hasMany(EmployeeFamily::class);
    }

    /**
     * Get the employee's face attendances.
     */
    public function faceAttendances(): HasMany
    {
        return $this->hasMany(FaceAttendance::class);
    }

    /**
     * Check if employee has registered face
     */
    public function hasRegisteredFace(): bool
    {
        return !is_null($this->face_photo_path) && !is_null($this->face_registered_at);
    }

    /**
     * Get face photo URL
     */
    public function getFacePhotoUrlAttribute()
    {
        if ($this->face_photo_path) {
            return Storage::disk('public')->url($this->face_photo_path);
        }
        return null;
    }
}
