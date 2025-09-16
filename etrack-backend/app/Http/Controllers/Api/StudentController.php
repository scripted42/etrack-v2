<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Student;
use App\Models\User;
use App\Models\AuditLog;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class StudentController extends Controller
{
    /**
     * Display a listing of students
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        if (!$user->role->permissions->contains('name', 'manage_students')) {
            return response()->json([
                'success' => false,
                'message' => 'Tidak memiliki akses untuk mengelola data siswa'
            ], 403);
        }

        $query = Student::with(['user.role','identity','contact','guardians']);

        // Filter by status
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        // Filter by kelas
        if ($request->has('kelas')) {
            $query->where('kelas', $request->kelas);
        }

        // Search by name or NIS
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('nama', 'like', "%{$search}%")
                  ->orWhere('nis', 'like', "%{$search}%");
            });
        }

        $perPage = $request->get('per_page', 10);
        
        // Handle per_page = -1 (show all)
        if ($perPage == -1) {
            $students = $query->get();
            return response()->json([
                'success' => true,
                'data' => [
                    'data' => $students,
                    'total' => $students->count(),
                    'current_page' => 1,
                    'last_page' => 1,
                    'per_page' => -1,
                    'from' => 1,
                    'to' => $students->count()
                ]
            ]);
        }
        
        $students = $query->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => [
                'data' => $students->items(),
                'total' => $students->total(),
                'current_page' => $students->currentPage(),
                'last_page' => $students->lastPage(),
                'per_page' => $students->perPage(),
                'from' => $students->firstItem(),
                'to' => $students->lastItem()
            ]
        ]);
    }

    /**
     * Store a newly created student
     */
    public function store(Request $request): JsonResponse
    {
        $user = $request->user();

        if (!$user->role->permissions->contains('name', 'manage_students')) {
            return response()->json([
                'success' => false,
                'message' => 'Tidak memiliki akses untuk mengelola data siswa'
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            // user (opsional jika user_id sudah ada)
            'user_id' => 'nullable|exists:users,id',
            'username' => 'required_without:user_id|string|unique:users,username',
            'name' => 'required_without:user_id|string|max:255',
            'email' => 'nullable|email|unique:users,email',
            'password' => 'required_without:user_id|string|min:8',

            // student core
            'nis' => 'required|string|unique:students,nis',
            'nama' => 'required|string|max:150',
            'kelas' => 'required|string|max:50',
            // SMP: tanpa jurusan
            'status' => 'required|in:aktif,lulus,pindah',
            'photo' => 'sometimes|file|image|max:2048',

            // identity
            'identity.nik' => 'nullable|string|max:30',
            'identity.nisn' => 'nullable|string|max:30',
            'identity.tempat_lahir' => 'nullable|string|max:100',
            'identity.tanggal_lahir' => 'nullable|date',
            'identity.jenis_kelamin' => 'nullable|in:L,P',
            'identity.agama' => 'nullable|string|max:50',

            // contact
            'contact.alamat' => 'nullable|string|max:255',
            'contact.kota' => 'nullable|string|max:100',
            'contact.provinsi' => 'nullable|string|max:100',
            'contact.kode_pos' => 'nullable|string|max:20',
            'contact.no_hp' => 'nullable|string|max:30',
            'contact.email' => 'nullable|email|max:150',

            // wali (array)
            'wali' => 'nullable|array',
            'wali.*.nama' => 'required_with:wali|string|max:150',
            'wali.*.hubungan' => 'nullable|string|max:50',
            'wali.*.pekerjaan' => 'nullable|string|max:100',
            'wali.*.no_hp' => 'nullable|string|max:30',
            'wali.*.alamat' => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            error_log('Validation failed: ' . json_encode($validator->errors()));
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        DB::beginTransaction();
        try {
            // Handle optional photo upload
            $photoPath = null;
            if ($request->hasFile('photo')) {
                $photoPath = $request->file('photo')->store('students', 'public');
            }
            // Create or use existing user
            if ($request->filled('user_id')) {
                $newUser = User::findOrFail($request->user_id);
            } else {
                // fallback email jika tidak dikirim (kolom email NOT NULL)
                $fallbackEmail = $request->email ?? ($request->username.'@student.local');
                $newUser = User::create([
                    'username' => $request->username,
                    'name' => $request->name,
                    'email' => $fallbackEmail,
                    'password' => Hash::make($request->password),
                    'role_id' => 3, // Siswa role
                    'status' => 'aktif',
                ]);
            }

            // Create student profile
            $student = Student::create([
                'user_id' => $newUser->id,
                'nis' => $request->nis,
                'nama' => $request->nama,
                'kelas' => $request->kelas,
                'status' => $request->status,
                'qr_value' => $request->input('qr_value', $request->nis),
                'photo_path' => $photoPath,
            ]);

            // Optional details
            if ($request->filled('identity')) {
                $student->identity()->create($request->input('identity'));
            }
            if ($request->filled('contact')) {
                $student->contact()->create($request->input('contact'));
            }
            if ($request->filled('wali')) {
                foreach ($request->input('wali') as $g) {
                    $student->guardians()->create($g);
                }
            }

            // Log activity
            AuditLog::create([
                'user_id' => $user->id,
                'action' => 'CREATE_STUDENT',
                'details' => [
                    'student_id' => $student->id,
                    'nis' => $student->nis,
                    'nama' => $student->nama,
                ],
                'ip_address' => $request->ip(),
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Data siswa berhasil ditambahkan',
                'data' => $student->load(['user.role','identity','contact','guardians','health'])
            ], 201);

        } catch (\Exception $e) {
            DB::rollback();
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan saat menyimpan data',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified student
     */
    public function show(Request $request, Student $student): JsonResponse
    {
        $user = $request->user();

        if (!$user->role->permissions->contains('name', 'manage_students')) {
            return response()->json([
                'success' => false,
                'message' => 'Tidak memiliki akses untuk mengelola data siswa'
            ], 403);
        }

        return response()->json([
            'success' => true,
            'data' => $student->load(['user.role','identity','contact','guardians'])
        ]);
    }

    /**
     * Update the specified student
     */
    public function update(Request $request, Student $student): JsonResponse
    {
        $user = $request->user();

        if (!$user->role->permissions->contains('name', 'manage_students')) {
            return response()->json([
                'success' => false,
                'message' => 'Tidak memiliki akses untuk mengelola data siswa'
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'username' => 'sometimes|required|string|unique:users,username,' . $student->user_id,
            'name' => 'sometimes|required|string|max:255',
            'email' => 'sometimes|required|email|unique:users,email,' . $student->user_id,
            'nis' => 'sometimes|required|string|unique:students,nis,' . $student->id,
            'nama' => 'sometimes|required|string|max:150',
            'kelas' => 'sometimes|required|string|max:50',
            'status' => 'sometimes|required|in:aktif,lulus,pindah',
            'qr_value' => 'sometimes|nullable|string|max:255',
            'photo' => 'sometimes|file|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);


        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        DB::beginTransaction();
        try {
            // Update user
            if ($request->has('username') || $request->has('name') || $request->has('email')) {
                $student->user->update($request->only(['username', 'name', 'email']));
            }

            // Handle optional photo upload
            $updateData = $request->only(['nis', 'nama', 'kelas', 'status', 'qr_value']);
            if ($request->hasFile('photo')) {
                \Log::info('Photo upload detected', [
                    'file_name' => $request->file('photo')->getClientOriginalName(),
                    'file_size' => $request->file('photo')->getSize(),
                    'file_mime' => $request->file('photo')->getMimeType(),
                ]);
                $path = $request->file('photo')->store('students', 'public');
                $updateData['photo_path'] = $path;
                \Log::info('Photo stored at path: ' . $path);
            } else {
                \Log::info('No photo file in request');
            }
            // Update student
            $student->update($updateData);

            // Log activity
            AuditLog::create([
                'user_id' => $user->id,
                'action' => 'UPDATE_STUDENT',
                'details' => [
                    'student_id' => $student->id,
                    'nis' => $student->nis,
                    'nama' => $student->nama,
                    'changes' => $request->all(),
                ],
                'ip_address' => $request->ip(),
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Data siswa berhasil diperbarui',
                'data' => $student->load(['user.role','identity','contact','guardians'])
            ]);

        } catch (\Exception $e) {
            DB::rollback();
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan saat memperbarui data',
                'error' => $e->getMessage(),
                'debug' => [
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                    'has_photo' => $request->hasFile('photo'),
                    'photo_size' => $request->hasFile('photo') ? $request->file('photo')->getSize() : null,
                    'photo_mime' => $request->hasFile('photo') ? $request->file('photo')->getMimeType() : null,
                ]
            ], 500);
        }
    }

    /**
     * Update student photo
     */
    public function updatePhoto(Request $request, Student $student): JsonResponse
    {
        $user = $request->user();

        if (!$user->role->permissions->contains('name', 'manage_students')) {
            return response()->json([
                'success' => false,
                'message' => 'Tidak memiliki akses untuk mengelola data siswa'
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'photo' => 'required|file|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $path = $request->file('photo')->store('students', 'public');
            $student->update(['photo_path' => $path]);

            return response()->json([
                'success' => true,
                'message' => 'Foto siswa berhasil diperbarui',
                'data' => $student->load(['user.role','identity','contact','guardians'])
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan saat memperbarui foto',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified student
     */
    public function destroy(Request $request, Student $student): JsonResponse
    {
        $user = $request->user();

        if (!$user->role->permissions->contains('name', 'manage_students')) {
            return response()->json([
                'success' => false,
                'message' => 'Tidak memiliki akses untuk mengelola data siswa'
            ], 403);
        }

        DB::beginTransaction();
        try {
            // Log activity before deletion
            AuditLog::create([
                'user_id' => $user->id,
                'action' => 'DELETE_STUDENT',
                'details' => [
                    'student_id' => $student->id,
                    'nis' => $student->nis,
                    'nama' => $student->nama,
                ],
                'ip_address' => $request->ip(),
            ]);

            // Hapus user agar username (NIS) tidak tertinggal dan menghalangi import ulang
            if ($student->user) {
                // Menghapus user akan menghapus student melalui cascade jika FK diset cascade
                $student->user->delete();
            } else {
                // Fallback jika relasi user tidak ada
                $student->delete();
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Data siswa berhasil dihapus'
            ]);

        } catch (\Exception $e) {
            DB::rollback();
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan saat menghapus data',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
