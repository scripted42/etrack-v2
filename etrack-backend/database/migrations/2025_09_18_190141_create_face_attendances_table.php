<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('face_attendances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained()->onDelete('cascade');
            $table->date('attendance_date');
            $table->time('attendance_time');
            $table->enum('attendance_type', ['early', 'on_time', 'late', 'overtime'])->default('on_time');
            $table->string('photo_path')->nullable();
            $table->decimal('confidence_score', 3, 2)->default(0.95);
            $table->string('location', 255)->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->enum('status', ['present', 'absent', 'late'])->default('present');
            $table->timestamps();
            
            // Indexes
            $table->index(['employee_id', 'attendance_date']);
            $table->index(['attendance_date', 'attendance_type']);
            $table->index('attendance_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('face_attendances');
    }
};
