<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmployeeFamily extends Model
{
    use HasFactory;

    protected $fillable = [
        'employee_id',
        'nama',
        'hubungan',
        'tanggal_lahir',
        'pekerjaan',
        'no_hp',
    ];

    protected $casts = [
        'tanggal_lahir' => 'date',
    ];

    /**
     * Get the employee that owns the family.
     */
    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }
}