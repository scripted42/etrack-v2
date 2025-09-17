<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Imports\SimpleStudentImport;
use App\Imports\EmployeeImport;
use App\Exports\StudentTemplateExport;
use App\Exports\EmployeeTemplateExport;
use App\Models\AuditLog;
use App\Services\AuditService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;
use Carbon\Carbon;

class ImportController extends Controller
{
    /**
     * Import students from Excel/CSV
     */
    public function importStudents(Request $request): JsonResponse
    {
        // Check if file is uploaded
        if (!$request->hasFile('file')) {
            return response()->json([
                'success' => false,
                'message' => 'File tidak ditemukan',
                'debug' => [
                    'file_uploaded' => false,
                    'request_data' => $request->all()
                ]
            ], 422);
        }

        $file = $request->file('file');
        
        // Check file size
        if ($file->getSize() > 10240 * 1024) { // 10MB
            return response()->json([
                'success' => false,
                'message' => 'File terlalu besar. Maksimal 10MB',
                'debug' => [
                    'file_size' => $file->getSize(),
                    'max_size' => 10240 * 1024
                ]
            ], 422);
        }

        // Check file extension
        $allowedExtensions = ['xlsx', 'xls', 'csv'];
        $fileExtension = strtolower($file->getClientOriginalExtension());
        
        if (!in_array($fileExtension, $allowedExtensions)) {
            return response()->json([
                'success' => false,
                'message' => 'Format file tidak didukung. Gunakan .xlsx, .xls, atau .csv',
                'debug' => [
                    'file_extension' => $fileExtension,
                    'allowed_extensions' => $allowedExtensions,
                    'file_mime' => $file->getMimeType()
                ]
            ], 422);
        }

        try {
            $userId = $request->user()->id;
            \Log::info('Starting import with file:', ['file_name' => $file->getClientOriginalName(), 'file_size' => $file->getSize()]);
            $import = new SimpleStudentImport($userId);
            
            Excel::import($import, $file);
            \Log::info('Import completed', ['imported_count' => $import->getImportedCount(), 'failed_count' => $import->getFailedCount()]);

            // Log import history
            $this->logImportHistory('students', $file->getClientOriginalName(), $import->getImportedCount(), $import->getFailedCount(), $userId);

            return response()->json([
                'success' => true,
                'message' => 'Import siswa berhasil',
                'data' => [
                    'imported_count' => $import->getImportedCount(),
                    'failed_count' => $import->getFailedCount(),
                    'errors' => $import->getImportErrors()
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengimport data siswa',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Import employees from Excel/CSV
     */
    public function importEmployees(Request $request): JsonResponse
    {
        // Check if file is uploaded
        if (!$request->hasFile('file')) {
            return response()->json([
                'success' => false,
                'message' => 'File tidak ditemukan',
                'debug' => [
                    'file_uploaded' => false,
                    'request_data' => $request->all()
                ]
            ], 422);
        }

        $file = $request->file('file');
        
        // Check file size
        if ($file->getSize() > 10240 * 1024) { // 10MB
            return response()->json([
                'success' => false,
                'message' => 'File terlalu besar. Maksimal 10MB',
                'debug' => [
                    'file_size' => $file->getSize(),
                    'max_size' => 10240 * 1024
                ]
            ], 422);
        }

        // Check file extension
        $allowedExtensions = ['xlsx', 'xls', 'csv'];
        $fileExtension = strtolower($file->getClientOriginalExtension());
        
        if (!in_array($fileExtension, $allowedExtensions)) {
            return response()->json([
                'success' => false,
                'message' => 'Format file tidak didukung. Gunakan .xlsx, .xls, atau .csv',
                'debug' => [
                    'file_extension' => $fileExtension,
                    'allowed_extensions' => $allowedExtensions,
                    'file_mime' => $file->getMimeType()
                ]
            ], 422);
        }

        try {
            $userId = $request->user()->id;
            $import = new EmployeeImport($userId);
            
            Excel::import($import, $file);

            // Log import history
            $this->logImportHistory('employees', $file->getClientOriginalName(), $import->getImportedCount(), $import->getFailedCount(), $userId);

            return response()->json([
                'success' => true,
                'message' => 'Import pegawai berhasil',
                'data' => [
                    'imported_count' => $import->getImportedCount(),
                    'failed_count' => $import->getFailedCount(),
                    'errors' => $import->getImportErrors()
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengimport data pegawai',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Download template for student import
     */
    public function downloadStudentTemplate()
    {
        try {
            $template = new StudentTemplateExport();
            $fileName = 'template_import_siswa_' . date('Y-m-d_H-i-s') . '.xlsx';
            
            return Excel::download($template, $fileName);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal membuat template',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Download template for employee import
     */
    public function downloadEmployeeTemplate()
    {
        try {
            $template = new EmployeeTemplateExport();
            $fileName = 'template_import_pegawai_' . date('Y-m-d_H-i-s') . '.xlsx';
            
            return Excel::download($template, $fileName);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal membuat template',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get import history
     */
    public function getImportHistory(Request $request): JsonResponse
    {
        try {
            $history = DB::table('import_logs')
                ->orderBy('created_at', 'desc')
                ->limit(50)
                ->get();

            return response()->json([
                'success' => true,
                'data' => $history
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil riwayat import',
                'error' => $e->getMessage()
            ], 500);
    }
}

/**
     * Log import history
     */
    private function logImportHistory(string $type, string $fileName, int $importedCount, int $failedCount, int $userId): void
    {
        try {
            DB::table('import_logs')->insert([
                'type' => $type,
                'file_name' => $fileName,
                'total_rows' => $importedCount + $failedCount,
                'imported_count' => $importedCount,
                'failed_count' => $failedCount,
                'status' => $failedCount > 0 ? ($importedCount > 0 ? 'partial' : 'failed') : 'success',
                'user_id' => $userId,
                'created_at' => now(),
                'updated_at' => now()
            ]);
        } catch (\Exception $e) {
            // Log error but don't fail the import
            \Log::error('Failed to log import history: ' . $e->getMessage());
        }
    }
}

