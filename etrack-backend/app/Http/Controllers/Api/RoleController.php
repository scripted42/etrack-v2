<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Role;
use App\Models\Permission;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

class RoleController extends Controller
{
    /**
     * Get all roles with permissions
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $roles = Role::with('permissions')->get();
            
            return response()->json([
                'success' => true,
                'data' => $roles
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil data roles',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get specific role with permissions
     */
    public function show(Role $role): JsonResponse
    {
        try {
            $role->load('permissions');
            
            return response()->json([
                'success' => true,
                'data' => $role
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil data role',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Create new role
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255|unique:roles,name',
            'display_name' => 'required|string|max:255',
            'description' => 'nullable|string|max:500',
            'permissions' => 'nullable|array',
            'permissions.*' => 'exists:permissions,id'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Data tidak valid',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            DB::beginTransaction();

            $role = Role::create([
                'name' => $request->name,
                'display_name' => $request->display_name,
                'description' => $request->description
            ]);

            if ($request->has('permissions')) {
                $role->permissions()->sync($request->permissions);
            }

            DB::commit();

            $role->load('permissions');

            return response()->json([
                'success' => true,
                'message' => 'Role berhasil dibuat',
                'data' => $role
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Gagal membuat role',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update role
     */
    public function update(Request $request, Role $role): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255|unique:roles,name,' . $role->id,
            'display_name' => 'required|string|max:255',
            'description' => 'nullable|string|max:500',
            'permissions' => 'nullable|array',
            'permissions.*' => 'exists:permissions,id'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Data tidak valid',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            DB::beginTransaction();

            $role->update([
                'name' => $request->name,
                'display_name' => $request->display_name,
                'description' => $request->description
            ]);

            if ($request->has('permissions')) {
                $role->permissions()->sync($request->permissions);
            }

            DB::commit();

            $role->load('permissions');

            return response()->json([
                'success' => true,
                'message' => 'Role berhasil diperbarui',
                'data' => $role
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Gagal memperbarui role',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Delete role
     */
    public function destroy(Role $role): JsonResponse
    {
        try {
            // Check if role is being used by users
            if ($role->users()->count() > 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'Role tidak dapat dihapus karena masih digunakan oleh user'
                ], 422);
            }

            $role->permissions()->detach();
            $role->delete();

            return response()->json([
                'success' => true,
                'message' => 'Role berhasil dihapus'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal menghapus role',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get all permissions
     */
    public function getPermissions(): JsonResponse
    {
        try {
            $permissions = Permission::all();
            
            return response()->json([
                'success' => true,
                'data' => $permissions
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil data permissions',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get role statistics
     */
    public function getStatistics(): JsonResponse
    {
        try {
            $totalRoles = Role::count();
            $totalPermissions = Permission::count();
            $rolesWithUsers = Role::has('users')->count();
            
            $roleUsage = Role::withCount('users')->get()->map(function ($role) {
                return [
                    'role_name' => $role->display_name,
                    'user_count' => $role->users_count
                ];
            });

            return response()->json([
                'success' => true,
                'data' => [
                    'total_roles' => $totalRoles,
                    'total_permissions' => $totalPermissions,
                    'roles_with_users' => $rolesWithUsers,
                    'role_usage' => $roleUsage
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil statistik roles',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
