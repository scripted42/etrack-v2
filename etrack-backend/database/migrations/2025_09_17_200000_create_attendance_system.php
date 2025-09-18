<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Attendance Records Table
        Schema::create('attendances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->enum('type', ['student', 'employee']);
            $table->date('attendance_date');
            $table->time('check_in_time')->nullable();
            $table->time('check_out_time')->nullable();
            $table->enum('status', ['present', 'late', 'absent', 'half_day']);
            $table->text('notes')->nullable();
            $table->timestamps();
            
            $table->index(['user_id', 'attendance_date']);
            $table->unique(['user_id', 'attendance_date']);
        });

        // Attendance Verification Table (3 Key Success Factors)
        Schema::create('attendance_verifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('attendance_id')->constrained()->onDelete('cascade');
            $table->json('gps_location')->nullable(); // {lat, lng, accuracy, address}
            $table->string('selfie_photo_path')->nullable();
            $table->string('qr_code_scanned')->nullable();
            $table->string('device_info')->nullable(); // browser, OS, etc
            $table->string('ip_address');
            $table->timestamp('verified_at');
            $table->timestamps();
        });

        // QR Code Sessions Table
        Schema::create('qr_code_sessions', function (Blueprint $table) {
            $table->id();
            $table->string('session_token', 64)->unique();
            $table->string('qr_code_data');
            $table->timestamp('generated_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // Attendance Rules & Settings
        Schema::create('attendance_settings', function (Blueprint $table) {
            $table->id();
            $table->string('setting_key')->unique();
            $table->text('setting_value');
            $table->text('description')->nullable();
            $table->timestamps();
        });

        // Attendance Statistics
        Schema::create('attendance_statistics', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->integer('year');
            $table->integer('month');
            $table->integer('total_days');
            $table->integer('present_days');
            $table->integer('late_days');
            $table->integer('absent_days');
            $table->decimal('attendance_percentage', 5, 2);
            $table->timestamps();
            
            $table->unique(['user_id', 'year', 'month']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attendance_statistics');
        Schema::dropIfExists('attendance_settings');
        Schema::dropIfExists('qr_code_sessions');
        Schema::dropIfExists('attendance_verifications');
        Schema::dropIfExists('attendances');
    }
};
