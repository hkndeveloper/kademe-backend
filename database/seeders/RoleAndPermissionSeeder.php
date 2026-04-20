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

        foreach ($roles as $roleName) {
            foreach (['web', 'api'] as $guard) {
                Role::firstOrCreate(['name' => $roleName, 'guard_name' => $guard]);
            }
        }

        // 3. Rollere İzinleri Atama
        foreach (['web', 'api'] as $guard) {
            $superAdmin = Role::findByName('super-admin', $guard);
            $superAdmin->syncPermissions(Permission::where('guard_name', $guard)->get());

            $coordinator = Role::findByName('coordinator', $guard);
            $coordinator->syncPermissions(
                Permission::whereIn('name', [
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
                ])->where('guard_name', $guard)->get()
            );
        }
        
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
