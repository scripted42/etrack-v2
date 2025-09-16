<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmployeeContact extends Model
{
    use HasFactory;

    protected $fillable = [
        'employee_id',
        'alamat',
        'kota',
        'provinsi',
        'kode_pos',
        'no_hp',
        'email',
    ];

    /**
     * Get the employee that owns the contact.
     */
    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }
}