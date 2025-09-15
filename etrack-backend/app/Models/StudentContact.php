<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StudentContact extends Model
{
    use HasFactory;

    protected $fillable = [
        'student_id', 'alamat', 'kota', 'provinsi', 'kode_pos', 'no_hp', 'email'
    ];

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }
}


