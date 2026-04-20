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
            'manage_project',
            'manage-participants',
            'manage-blacklist',
            'manage-applications',
            'evaluate_applications',
            'write-blog',
            'view-audit-logs',
            'manage-announcements',
            'send_sms_email',
            'view-calendar',
            'take_attendance',
            'manage-coordinators',
            'manage-gamification',
            'manage-kpd',
            'manage-settings',
            'manage-permissions',
            'manage-users',
            'upload_materials',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'api']);
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
            Role::firstOrCreate(['name' => $roleName, 'guard_name' => 'api']);
        }

        // 3. Rollere İzinleri Atama
        // Super admin tüm izinleri alır
        $roleObjects['super-admin']->syncPermissions(Permission::all());

        // Coordinator proje düzeyindeki operasyonel izinleri alır
        $roleObjects['coordinator']->syncPermissions([
            'view-dashboard',
            'manage-projects',
            'manage_project',
            'manage-participants',
            'manage-applications',
            'evaluate_applications',
            'view-calendar',
            'take_attendance',
            'manage-kpd',
            'upload_materials',
        ]);
        
        // 4. İlk Sistem Yöneticisi
        $admin = User::firstOrCreate(
            ['email' => 'admin@kademe.org'],
            [
                'name' => 'KADEME Üst Admin',
                'password' => Hash::make('password123'),
            ]
        );

        $admin->assignRole('super-admin');
    }
}
