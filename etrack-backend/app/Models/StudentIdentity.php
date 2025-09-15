<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StudentIdentity extends Model
{
    use HasFactory;

    protected $fillable = [
        'student_id', 'nik', 'nisn', 'tempat_lahir', 'tanggal_lahir', 'jenis_kelamin', 'agama'
    ];

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }
}


