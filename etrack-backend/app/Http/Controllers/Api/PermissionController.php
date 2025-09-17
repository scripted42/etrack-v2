<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Permission;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;

class PermissionController extends Controller
{
    /**
     * Get all permissions
     */
    public function index(Request $request): JsonResponse
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
     * Get specific permission
     */
    public function show(Permission $permission): JsonResponse
    {
        try {
            return response()->json([
                'success' => true,
                'data' => $permission
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil data permission',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Create new permission
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255|unique:permissions,name',
            'display_name' => 'required|string|max:255',
            'description' => 'nullable|string|max:500',
            'group' => 'nullable|string|max:100'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Data tidak valid',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $permission = Permission::create([
                'name' => $request->name,
                'display_name' => $request->display_name,
                'description' => $request->description,
                'group' => $request->group
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Permission berhasil dibuat',
                'data' => $permission
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal membuat permission',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update permission
     */
    public function update(Request $request, Permission $permission): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255|unique:permissions,name,' . $permission->id,
            'display_name' => 'required|string|max:255',
            'description' => 'nullable|string|max:500',
            'group' => 'nullable|string|max:100'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Data tidak valid',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $permission->update([
                'name' => $request->name,
                'display_name' => $request->display_name,
                'description' => $request->description,
                'group' => $request->group
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Permission berhasil diperbarui',
                'data' => $permission
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal memperbarui permission',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Delete permission
     */
    public function destroy(Permission $permission): JsonResponse
    {
        try {
            // Check if permission is being used by roles
            if ($permission->roles()->count() > 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'Permission tidak dapat dihapus karena masih digunakan oleh role'
                ], 422);
            }

            $permission->delete();

            return response()->json([
                'success' => true,
                'message' => 'Permission berhasil dihapus'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal menghapus permission',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get permissions grouped by category
     */
    public function getGroupedPermissions(): JsonResponse
    {
        try {
            $permissions = Permission::all()->groupBy('group');
            
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
}
