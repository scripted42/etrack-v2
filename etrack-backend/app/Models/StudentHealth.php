<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StudentHealth extends Model
{
    use HasFactory;

    protected $table = 'student_health';

    protected $fillable = [
        'student_id', 'alergi', 'disabilitas', 'catatan_medis'
    ];

    protected $casts = [
        // future: consider encryption via casts
    ];

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }
}


