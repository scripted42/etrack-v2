<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;

class SecureFileUploadService
{
    /**
     * Upload file with security checks
     */
    public function uploadFile(UploadedFile $file, string $directory): array
    {
        // Validate file type
        $allowedTypes = ['jpg', 'jpeg', 'png', 'pdf', 'doc', 'docx', 'xls', 'xlsx'];
        $extension = strtolower($file->getClientOriginalExtension());
        
        if (!in_array($extension, $allowedTypes)) {
            throw new \Exception('File type not allowed. Allowed types: ' . implode(', ', $allowedTypes));
        }
        
        // Validate file size (max 5MB)
        if ($file->getSize() > 5 * 1024 * 1024) {
            throw new \Exception('File size too large. Maximum size: 5MB');
        }
        
        // Scan for malware (basic check)
        if ($this->containsMalware($file)) {
            throw new \Exception('File contains potential malware');
        }
        
        // Generate secure filename
        $filename = Str::uuid() . '.' . $extension;
        $path = $file->storeAs($directory, $filename, 'private');
        
        return [
            'filename' => $filename,
            'path' => $path,
            'size' => $file->getSize(),
            'mime_type' => $file->getMimeType(),
            'original_name' => $file->getClientOriginalName(),
            'uploaded_at' => now()->toISOString()
        ];
    }
    
    /**
     * Check if file contains potential malware
     */
    private function containsMalware(UploadedFile $file): bool
    {
        $content = file_get_contents($file->getPathname());
        
        $malwarePatterns = [
            '<?php', 'eval(', 'exec(', 'system(',
            'shell_exec(', 'passthru(', 'base64_decode(',
            'file_get_contents(', 'fopen(', 'fwrite(',
            'include(', 'require(', 'include_once(',
            'require_once(', 'create_function('
        ];
        
        foreach ($malwarePatterns as $pattern) {
            if (strpos($content, $pattern) !== false) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Delete file securely
     */
    public function deleteFile(string $path): bool
    {
        if (Storage::disk('private')->exists($path)) {
            return Storage::disk('private')->delete($path);
        }
        
        return false;
    }
    
    /**
     * Get file info
     */
    public function getFileInfo(string $path): array
    {
        if (!Storage::disk('private')->exists($path)) {
            throw new \Exception('File not found');
        }
        
        return [
            'path' => $path,
            'size' => Storage::disk('private')->size($path),
            'mime_type' => Storage::disk('private')->mimeType($path),
            'last_modified' => Storage::disk('private')->lastModified($path)
        ];
    }
}

