<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class EmployeeImportController extends Controller
{
    public function import(Request $request): JsonResponse
    {
        $user = $request->user();

        if (!$user->role->permissions->contains('name', 'manage_employees')) {
            return response()->json([
                'success' => false,
                'message' => 'Tidak memiliki akses untuk mengelola data pegawai'
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'file' => 'required|file|mimes:csv,txt|max:2048',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $file = $request->file('file');
            $csvData = array_map('str_getcsv', file($file->getPathname()));
            $headers = array_shift($csvData);

            // Expected headers
            $expectedHeaders = [
                'nip', 'nama', 'jabatan', 'status', 'username', 'email', 'password',
                'nik', 'tempat_lahir', 'tanggal_lahir', 'jenis_kelamin', 'agama',
                'alamat', 'kota', 'provinsi', 'kode_pos', 'no_hp', 'email_contact'
            ];

            // Validate headers
            $missingHeaders = array_diff($expectedHeaders, $headers);
            if (!empty($missingHeaders)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Header CSV tidak lengkap. Header yang diperlukan: ' . implode(', ', $expectedHeaders)
                ], 422);
            }

            $successCount = 0;
            $failedCount = 0;
            $errors = [];

            // Get employee role
            $pegawaiRole = Role::where('name', 'Guru')->first();
            if (!$pegawaiRole) {
                return response()->json([
                    'success' => false,
                    'message' => 'Role Guru tidak ditemukan'
                ], 500);
            }

            DB::beginTransaction();

            foreach ($csvData as $lineNumber => $row) {
                try {
                    $recordData = array_combine($headers, $row);
                    
                    // Skip empty rows
                    if (empty(array_filter($recordData))) {
                        continue;
                    }

                    // Validate required fields
                    if (empty($recordData['nip']) || empty($recordData['nama']) || empty($recordData['jabatan'])) {
                        $failedCount++;
                        $errors[] = [
                            'line' => $lineNumber + 2,
                            'messages' => ['NIP, Nama, dan Jabatan harus diisi']
                        ];
                        continue;
                    }

                    // Check if NIP already exists
                    if (Employee::where('nip', $recordData['nip'])->exists()) {
                        $failedCount++;
                        $errors[] = [
                            'line' => $lineNumber + 2,
                            'messages' => ["NIP {$recordData['nip']} sudah ada - data pegawai dengan NIP ini sudah terdaftar"]
                        ];
                        continue;
                    }

                    // Check if username already exists and handle duplicates
                    $username = $recordData['username'] ?: $recordData['nip'];
                    $originalUsername = $username;
                    $counter = 1;
                    
                    // If username exists, try to find a unique one by appending number
                    while (User::where('username', $username)->exists()) {
                        $username = $originalUsername . '_' . $counter;
                        $counter++;
                        
                        // Prevent infinite loop
                        if ($counter > 100) {
                            $failedCount++;
                            $errors[] = [
                                'line' => $lineNumber + 2,
                                'messages' => ["Tidak dapat membuat username unik untuk {$originalUsername}"]
                            ];
                            continue 2; // Skip to next row
                        }
                    }

                    // Handle email duplicates
                    $email = $recordData['email'] ?: $username . '@employee.local';
                    $originalEmail = $email;
                    $emailCounter = 1;
                    
                    while (User::where('email', $email)->exists()) {
                        $email = $originalEmail . '_' . $emailCounter;
                        $emailCounter++;
                        
                        if ($emailCounter > 100) {
                            $email = $username . '_' . time() . '@employee.local';
                            break;
                        }
                    }

                    // Create user
                    $newUser = User::create([
                        'username' => $username,
                        'name' => $recordData['nama'],
                        'email' => $email,
                        'password' => Hash::make($recordData['password'] ?: 'password123'),
                        'role_id' => $pegawaiRole->id,
                        'status' => 'aktif',
                    ]);

                    // Create employee
                    $employee = $newUser->employee()->create([
                        'nip' => $recordData['nip'],
                        'nama' => $recordData['nama'],
                        'jabatan' => $recordData['jabatan'],
                        'status' => $recordData['status'] ?: 'aktif',
                        'qr_value' => $recordData['nip'],
                    ]);

                    // Create identity if data exists
                    if (!empty($recordData['nik']) || !empty($recordData['tempat_lahir'])) {
                        $employee->identity()->create([
                            'nik' => $recordData['nik'] ?: null,
                            'tempat_lahir' => $recordData['tempat_lahir'] ?: null,
                            'tanggal_lahir' => $recordData['tanggal_lahir'] ?: null,
                            'jenis_kelamin' => $recordData['jenis_kelamin'] ?: null,
                            'agama' => $recordData['agama'] ?: null,
                        ]);
                    }

                    // Create contact if data exists
                    if (!empty($recordData['alamat']) || !empty($recordData['no_hp'])) {
                        $employee->contact()->create([
                            'alamat' => $recordData['alamat'] ?: null,
                            'kota' => $recordData['kota'] ?: null,
                            'provinsi' => $recordData['provinsi'] ?: null,
                            'kode_pos' => $recordData['kode_pos'] ?: null,
                            'no_hp' => $recordData['no_hp'] ?: null,
                            'email' => $recordData['email_contact'] ?: null,
                        ]);
                    }

                    // Log if username or email was modified due to duplicates
                    $modifications = [];
                    if ($username !== $originalUsername) {
                        $modifications[] = "Username diubah dari '{$originalUsername}' menjadi '{$username}' karena duplikasi";
                    }
                    if ($email !== $originalEmail) {
                        $modifications[] = "Email diubah dari '{$originalEmail}' menjadi '{$email}' karena duplikasi";
                    }
                    
                    if (!empty($modifications)) {
                        $errors[] = [
                            'line' => $lineNumber + 2,
                            'messages' => $modifications,
                            'type' => 'warning'
                        ];
                    }

                    $successCount++;

                } catch (\Exception $e) {
                    $failedCount++;
                    $errors[] = [
                        'line' => $lineNumber + 2,
                        'messages' => [$e->getMessage()]
                    ];
                }
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Import selesai',
                'data' => [
                    'success_count' => $successCount,
                    'failed_count' => $failedCount,
                    'errors' => $errors
                ]
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan saat mengimport data',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function downloadTemplate(): JsonResponse
    {
        $user = request()->user();

        if (!$user->role->permissions->contains('name', 'manage_employees')) {
            return response()->json([
                'success' => false,
                'message' => 'Tidak memiliki akses untuk mengelola data pegawai'
            ], 403);
        }

        $headers = [
            'nip', 'nama', 'jabatan', 'status', 'username', 'email', 'password',
            'nik', 'tempat_lahir', 'tanggal_lahir', 'jenis_kelamin', 'agama',
            'alamat', 'kota', 'provinsi', 'kode_pos', 'no_hp', 'email_contact'
        ];

        $sampleData = [
            [
                'P999001', 'Dr. Siti Nurhaliza S.Pd. M.Pd.', 'Guru Matematika', 'aktif', 'guru999001', 'siti999001@example.com', 'password123',
                '3578011512650001', 'Surabaya', '1965-12-15', 'P', 'Islam',
                'Jl. Raya Darmo Permai III No. 15', 'Surabaya', 'Jawa Timur', '60111', '081234567890', 'siti.contact@example.com'
            ],
            [
                'P999002', 'Budi Santoso S.Pd.', 'Guru Bahasa Indonesia', 'aktif', 'guru999002', 'budi999002@example.com', 'password123',
                '3578011003720002', 'Malang', '1972-03-10', 'L', 'Kristen',
                'Jl. Raya Darmo Permai III No. 16', 'Surabaya', 'Jawa Timur', '60111', '081298765432', 'budi.contact@example.com'
            ]
        ];

        // Generate proper CSV content with quotes
        $output = fopen('php://temp', 'r+');
        
        // Write headers
        fputcsv($output, $headers);
        
        // Write sample data
        foreach ($sampleData as $row) {
            fputcsv($output, $row);
        }
        
        rewind($output);
        $csvContent = stream_get_contents($output);
        fclose($output);

        return response()->json([
            'success' => true,
            'data' => [
                'filename' => 'template_pegawai.csv',
                'content' => $csvContent
            ]
        ]);
    }
}
