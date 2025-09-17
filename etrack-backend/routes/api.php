<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\StudentController;
use App\Http\Controllers\Api\EmployeeController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\AuditLogController;
use App\Http\Controllers\Api\BackupController;
use App\Http\Controllers\Api\StudentImportController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

// Public routes
Route::post('/login', [AuthController::class, 'login']);

// Protected routes
Route::middleware('auth:sanctum')->group(function () {
    // Auth routes
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', [AuthController::class, 'me']);
    Route::post('/change-password', [AuthController::class, 'changePassword']);
    Route::post('/validate-password', [AuthController::class, 'validatePassword']);

    // Dashboard routes
    Route::get('/dashboard', [DashboardController::class, 'index']);
    Route::get('/dashboard/health', [DashboardController::class, 'health']);

    // Student routes
    Route::apiResource('students', StudentController::class);
    Route::post('/students/import', [StudentImportController::class, 'import']);
    Route::post('/students/{student}/photo', [StudentController::class, 'updatePhoto']);

// Employee routes
Route::post('/employees/import', [App\Http\Controllers\Api\EmployeeImportController::class, 'import']);
Route::get('/employees/template', [App\Http\Controllers\Api\EmployeeImportController::class, 'downloadTemplate']);
Route::apiResource('employees', EmployeeController::class);
Route::post('/employees/{employee}/photo', [EmployeeController::class, 'updatePhoto']);

    // User management routes
    Route::apiResource('users', UserController::class);

    // Audit log routes
    Route::get('/audit-logs', [AuditLogController::class, 'index']);
    Route::get('/audit-logs/{auditLog}', [AuditLogController::class, 'show']);
    Route::get('/audit-logs/statistics/overview', [AuditLogController::class, 'statistics']);
    Route::post('/audit-logs/export', [AuditLogController::class, 'export']);

    // Backup routes
    Route::get('/backups', [BackupController::class, 'index']);
    Route::post('/backups', [BackupController::class, 'create']);
    Route::get('/backups/statistics', [BackupController::class, 'statistics']);
    Route::get('/backups/config', [BackupController::class, 'config']);
    Route::post('/backups/test', [BackupController::class, 'test']);
    Route::post('/backups/{filename}/restore', [BackupController::class, 'restore']);
    Route::get('/backups/{filename}/download', [BackupController::class, 'download']);
    Route::delete('/backups/{filename}', [BackupController::class, 'destroy']);
});
