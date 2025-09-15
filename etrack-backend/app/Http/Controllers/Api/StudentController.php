<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Student;
use App\Models\User;
use App\Models\AuditLog;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;
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

        $query = Student::with(['user.role']);

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

        $students = $query->paginate($request->get('per_page', 15));

        return response()->json([
            'success' => true,
            'data' => $students
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
            'username' => 'required|string|unique:users,username',
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:8',
            'nis' => 'required|string|unique:students,nis',
            'nama' => 'required|string|max:150',
            'kelas' => 'required|string|max:50',
            'jurusan' => 'nullable|string|max:100',
            'status' => 'required|in:aktif,lulus,pindah',
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
            // Create user
            $newUser = User::create([
                'username' => $request->username,
                'name' => $request->name,
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'role_id' => 5, // Siswa role
                'status' => 'aktif',
            ]);

            // Create student profile
            $student = Student::create([
                'user_id' => $newUser->id,
                'nis' => $request->nis,
                'nama' => $request->nama,
                'kelas' => $request->kelas,
                'jurusan' => $request->jurusan,
                'status' => $request->status,
            ]);

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
                'data' => $student->load('user.role')
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
            'data' => $student->load(['user.role', 'user.documents'])
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
            'jurusan' => 'nullable|string|max:100',
            'status' => 'sometimes|required|in:aktif,lulus,pindah',
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

            // Update student
            $student->update($request->only(['nis', 'nama', 'kelas', 'jurusan', 'status']));

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
                'data' => $student->load('user.role')
            ]);

        } catch (\Exception $e) {
            DB::rollback();
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan saat memperbarui data',
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

            // Delete student (user will be deleted by cascade)
            $student->delete();

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
