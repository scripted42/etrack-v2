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
        Schema::create('documents', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('description')->nullable();
            $table->string('file_name');
            $table->string('file_path');
            $table->string('file_type');
            $table->integer('file_size');
            $table->string('document_type'); // 'student' or 'employee'
            $table->unsignedBigInteger('documentable_id'); // student_id or employee_id
            $table->string('documentable_type'); // 'App\Models\Student' or 'App\Models\Employee'
            $table->string('category')->nullable(); // 'identity', 'academic', 'employment', etc.
            $table->string('status')->default('active'); // 'active', 'archived', 'deleted'
            $table->foreignId('uploaded_by')->constrained('users')->onDelete('cascade');
            $table->timestamps();
            
            $table->index(['documentable_id', 'documentable_type']);
            $table->index(['document_type', 'category']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('documents');
    }
};
