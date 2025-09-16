<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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
}
