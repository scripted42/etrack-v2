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
        // Add photo_path and qr_value to employees table
        Schema::table('employees', function (Blueprint $table) {
            $table->string('photo_path', 255)->nullable()->after('status');
            $table->string('qr_value', 255)->nullable()->after('photo_path');
        });

        // Create employee_identities table
        Schema::create('employee_identities', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('employee_id');
            $table->string('nik', 30)->nullable();
            $table->string('tempat_lahir', 100)->nullable();
            $table->date('tanggal_lahir')->nullable();
            $table->enum('jenis_kelamin', ['L', 'P'])->nullable();
            $table->string('agama', 50)->nullable();
            $table->timestamps();

            $table->foreign('employee_id')->references('id')->on('employees')->onDelete('cascade');
        });

        // Create employee_contacts table
        Schema::create('employee_contacts', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('employee_id');
            $table->text('alamat')->nullable();
            $table->string('kota', 100)->nullable();
            $table->string('provinsi', 100)->nullable();
            $table->string('kode_pos', 20)->nullable();
            $table->string('no_hp', 30)->nullable();
            $table->string('email', 150)->nullable();
            $table->timestamps();

            $table->foreign('employee_id')->references('id')->on('employees')->onDelete('cascade');
        });

        // Create employee_families table (keluarga)
        Schema::create('employee_families', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('employee_id');
            $table->string('nama', 150);
            $table->string('hubungan', 50)->nullable(); // Suami, Istri, Anak, dll
            $table->date('tanggal_lahir')->nullable();
            $table->string('pekerjaan', 100)->nullable();
            $table->string('no_hp', 30)->nullable();
            $table->timestamps();

            $table->foreign('employee_id')->references('id')->on('employees')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('employee_families');
        Schema::dropIfExists('employee_contacts');
        Schema::dropIfExists('employee_identities');
        
        Schema::table('employees', function (Blueprint $table) {
            $table->dropColumn(['photo_path', 'qr_value']);
        });
    }
};