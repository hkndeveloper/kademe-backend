<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\PermissionRegistrar;

class RoleAndPermissionSeeder extends Seeder
{
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        // 1. İzinleri (Permissions) Tanımla - Frontend Ability İsimleriyle Senkronize
        $permissions = [
            'view-dashboard',
            'manage-projects',
            'manage-participants',
            'manage-blacklist',
            'manage-applications',
            'write-blog',
            'view-audit-logs',
            'manage-announcements',
            'view-calendar',
            'manage-coordinators',
            'manage-gamification',
            'manage-kpd',
            'manage-settings',
            'manage-permissions',
            'manage-users',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
        }

        // 2. Rolleri Tanımla
        $roles = [
            'super-admin',
            'coordinator',
            'staff',
            'student',
            'alumni',
            'guest'
        ];

        $roleObjects = [];
        foreach ($roles as $roleName) {
            $roleObjects[$roleName] = Role::firstOrCreate(['name' => $roleName, 'guard_name' => 'web']);
        }

        // 3. Rollere İzinleri Atama
        // Super admin tüm izinleri alır
        $roleObjects['super-admin']->syncPermissions(Permission::all());

        // Coordinator proje düzeyindeki operasyonel izinleri alır
        $roleObjects['coordinator']->syncPermissions([
            'view-dashboard',
            'manage-projects',
            'manage-participants',
            'manage-applications',
            'view-calendar',
            'manage-kpd',
        ]);
        
        // 4. İlk Sistem Yöneticisi
        $admin = User::firstOrCreate(
            ['email' => 'admin@kademe.org'],
            [
                'name' => 'KADEME Üst Admin',
                'password' => Hash::make('12ytrmhkn_46'),
            ]
        );

        $admin->assignRole('super-admin');
    }
}
