<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\User;
use App\Models\AuditLog;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class EmployeeController extends Controller
{
    /**
     * Display a listing of employees
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        if (!$user->role->permissions->contains('name', 'manage_employees')) {
            return response()->json([
                'success' => false,
                'message' => 'Tidak memiliki akses untuk mengelola data pegawai'
            ], 403);
        }

        $query = Employee::with(['user.role', 'identity', 'contact', 'families']);

        // Filter by status
        if ($request->has('status') && $request->status !== '' && $request->status !== null) {
            $query->where('status', $request->status);
        }

        // Filter by jabatan
        if ($request->has('jabatan') && $request->jabatan !== '' && $request->jabatan !== null) {
            $query->where('jabatan', 'like', '%' . $request->jabatan . '%');
        }

        // Search by name or NIP
        if ($request->has('search') && $request->search !== '' && $request->search !== null) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('nama', 'like', '%' . $search . '%')
                  ->orWhere('nip', 'like', '%' . $search . '%');
            });
        }

        $perPage = $request->get('per_page', 10);
        
        // Handle per_page = -1 (show all)
        if ($perPage == -1) {
            $employees = $query->get();
            return response()->json([
                'success' => true,
                'data' => [
                    'data' => $employees,
                    'total' => $employees->count(),
                    'current_page' => 1,
                    'last_page' => 1,
                    'per_page' => -1,
                    'from' => 1,
                    'to' => $employees->count()
                ]
            ]);
        }
        
        $employees = $query->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => [
                'data' => $employees->items(),
                'total' => $employees->total(),
                'current_page' => $employees->currentPage(),
                'last_page' => $employees->lastPage(),
                'per_page' => $employees->perPage(),
                'from' => $employees->firstItem(),
                'to' => $employees->lastItem()
            ]
        ]);
    }

    /**
     * Store a newly created employee
     */
    public function store(Request $request): JsonResponse
    {
        $user = $request->user();

        if (!$user->role->permissions->contains('name', 'manage_employees')) {
            return response()->json([
                'success' => false,
                'message' => 'Tidak memiliki akses untuk mengelola data pegawai'
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'username' => 'required|string|unique:users,username',
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:8',

            // employee core
            'nip' => 'required|string|unique:employees,nip',
            'nama' => 'required|string|max:150',
            'jabatan' => 'required|string|max:100',
            'status' => 'required|in:aktif,cuti,pensiun',
            'photo' => 'sometimes|file|image|mimes:jpeg,png,jpg,gif|max:2048',

            // identity
            'identity.nik' => 'nullable|string|max:30',
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

            // families (array)
            'families' => 'nullable|array',
            'families.*.nama' => 'required_with:families|string|max:150',
            'families.*.hubungan' => 'nullable|string|max:50',
            'families.*.tanggal_lahir' => 'nullable|date',
            'families.*.pekerjaan' => 'nullable|string|max:100',
            'families.*.no_hp' => 'nullable|string|max:30',
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
            // Get employee role (use Guru role for employees)
            $pegawaiRole = \App\Models\Role::where('name', 'Guru')->first();
            if (!$pegawaiRole) {
                return response()->json([
                    'success' => false,
                    'message' => 'Role Guru tidak ditemukan'
                ], 500);
            }

            // Create user
            $newUser = User::create([
                'username' => $request->username,
                'name' => $request->name,
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'role_id' => $pegawaiRole->id,
                'status' => 'aktif',
            ]);

            // Handle photo upload
            $photoPath = null;
            if ($request->hasFile('photo')) {
                $photoPath = $request->file('photo')->store('employees', 'public');
            }

            // Create employee
            $employee = Employee::create([
                'user_id' => $newUser->id,
                'nip' => $request->nip,
                'nama' => $request->nama,
                'jabatan' => $request->jabatan,
                'status' => $request->status,
                'photo_path' => $photoPath,
                'qr_value' => $request->nip, // Default QR value to NIP
            ]);

            // Create identity
            if ($request->has('identity')) {
                $employee->identity()->create($request->identity);
            }

            // Create contact
            if ($request->has('contact')) {
                $employee->contact()->create($request->contact);
            }

            // Create families
            if ($request->has('families') && is_array($request->families)) {
                foreach ($request->families as $familyData) {
                    $employee->families()->create($familyData);
                }
            }

            // Log activity
            AuditLog::create([
                'user_id' => $user->id,
                'action' => 'CREATE_EMPLOYEE',
                'details' => [
                    'employee_id' => $employee->id,
                    'nip' => $employee->nip,
                    'nama' => $employee->nama,
                ],
                'ip_address' => $request->ip(),
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Data pegawai berhasil ditambahkan',
                'data' => $employee->load(['user.role', 'identity', 'contact', 'families'])
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
     * Display the specified employee
     */
    public function show(Request $request, Employee $employee): JsonResponse
    {
        $user = $request->user();

        if (!$user->role->permissions->contains('name', 'manage_employees')) {
            return response()->json([
                'success' => false,
                'message' => 'Tidak memiliki akses untuk mengelola data pegawai'
            ], 403);
        }

        return response()->json([
            'success' => true,
            'data' => $employee->load(['user.role', 'identity', 'contact', 'families'])
        ]);
    }

    /**
     * Update the specified employee
     */
    public function update(Request $request, Employee $employee): JsonResponse
    {
        $user = $request->user();

        if (!$user->role->permissions->contains('name', 'manage_employees')) {
            return response()->json([
                'success' => false,
                'message' => 'Tidak memiliki akses untuk mengelola data pegawai'
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'username' => 'sometimes|required|string|unique:users,username,' . $employee->user_id,
            'name' => 'sometimes|required|string|max:255',
            'email' => 'sometimes|required|email|unique:users,email,' . $employee->user_id,
            'nip' => 'sometimes|required|string|unique:employees,nip,' . $employee->id,
            'nama' => 'sometimes|required|string|max:150',
            'jabatan' => 'sometimes|required|string|max:100',
            'status' => 'sometimes|required|in:aktif,cuti,pensiun',
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
                $employee->user->update($request->only(['username', 'name', 'email']));
            }

            // Handle photo upload
            $updateData = $request->only(['nip', 'nama', 'jabatan', 'status', 'qr_value']);
            if ($request->hasFile('photo')) {
                $path = $request->file('photo')->store('employees', 'public');
                $updateData['photo_path'] = $path;
            }

            // Update employee
            $employee->update($updateData);

            // Log activity
            AuditLog::create([
                'user_id' => $user->id,
                'action' => 'UPDATE_EMPLOYEE',
                'details' => [
                    'employee_id' => $employee->id,
                    'nip' => $employee->nip,
                    'nama' => $employee->nama,
                    'changes' => $request->all(),
                ],
                'ip_address' => $request->ip(),
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Data pegawai berhasil diperbarui',
                'data' => $employee->load(['user.role', 'identity', 'contact', 'families'])
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
     * Update employee photo
     */
    public function updatePhoto(Request $request, Employee $employee): JsonResponse
    {
        $user = $request->user();

        if (!$user->role->permissions->contains('name', 'manage_employees')) {
            return response()->json([
                'success' => false,
                'message' => 'Tidak memiliki akses untuk mengelola data pegawai'
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
            $path = $request->file('photo')->store('employees', 'public');
            $employee->update(['photo_path' => $path]);

            return response()->json([
                'success' => true,
                'message' => 'Foto pegawai berhasil diperbarui',
                'data' => $employee->load(['user.role', 'identity', 'contact', 'families'])
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
     * Remove the specified employee
     */
    public function destroy(Request $request, Employee $employee): JsonResponse
    {
        $user = $request->user();

        if (!$user->role->permissions->contains('name', 'manage_employees')) {
            return response()->json([
                'success' => false,
                'message' => 'Tidak memiliki akses untuk mengelola data pegawai'
            ], 403);
        }

        DB::beginTransaction();
        try {
            // Log activity before deletion
            AuditLog::create([
                'user_id' => $user->id,
                'action' => 'DELETE_EMPLOYEE',
                'details' => [
                    'employee_id' => $employee->id,
                    'nip' => $employee->nip,
                    'nama' => $employee->nama,
                ],
                'ip_address' => $request->ip(),
            ]);

            // Delete associated user
            $employee->user->delete();
            $employee->delete();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Data pegawai berhasil dihapus'
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
