<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RolePermissionController extends Controller
{
    /**
     * Tüm rolleri ve izinleri matris için getir
     */
    public function index()
    {
        $roles = Role::with('permissions')->get();
        $permissions = Permission::all();

        return response()->json([
            'roles' => $roles,
            'permissions' => $permissions
        ]);
    }

    /**
     * Rolün izinlerini güncelle
     */
    public function syncPermissions(Request $request, Role $role)
    {
        // Güvenlik: Super Admin rolü üzerinden yetki değişikliği yapılamasın
        if ($role->name === 'super-admin') {
            return response()->json(['message' => 'Süper Admin yetkileri değiştirilemez.'], 403);
        }

        $request->validate([
            'permissions' => 'array',
            'permissions.*' => 'string'
        ]);

        $role->syncPermissions($request->permissions);

        return response()->json([
            'message' => "{$role->name} rolünün yetkileri güncellendi.",
            'role' => $role->load('permissions')
        ]);
    }

    /**
     * Yeni rol oluştur
     */
    public function storeRole(Request $request)
    {
        $request->validate(['name' => 'required|string|unique:roles,name']);
        
        $role = Role::create([
            'name' => $request->name,
            'guard_name' => 'web'
        ]);

        return response()->json(['message' => 'Yeni rol oluşturuldu.', 'role' => $role], 201);
    }
}
