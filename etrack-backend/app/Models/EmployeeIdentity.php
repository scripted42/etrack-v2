<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmployeeIdentity extends Model
{
    use HasFactory;

    protected $fillable = [
        'employee_id',
        'nik',
        'tempat_lahir',
        'tanggal_lahir',
        'jenis_kelamin',
        'agama',
    ];

    protected $casts = [
        'tanggal_lahir' => 'date',
    ];

    /**
     * Get the employee that owns the identity.
     */
    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }
}