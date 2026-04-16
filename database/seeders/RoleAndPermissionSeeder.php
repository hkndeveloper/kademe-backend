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

        // 1. İzinleri (Permissions) Tanımla
        $permissions = [
            'manage_all_projects',   // Yalnızca Super Admin (global bypass)
            'manage_project',        // Proje ayarlarını düzenleme
            'evaluate_applications', // Başvuruları inceleme, kabul/red
            'take_attendance',       // Yoklama başlatma / manuel yoklama girme
            'upload_materials',      // Materyal yükleme / silme
            'send_sms_email',        // İletişim araçlarını kullanma
            'manage_forum',          // Forum mesajlarını yönetme
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
        }

        // 2. Rolleri Tanımla
        $roles = [
            'super-admin', // Her şeye yetkili
            'coordinator', // Projedeki tüm işlemlere yetkili
            'staff',       // Sadece yetki verildiği işlemlere (örn: yoklama) yetkili
            'student',     // Katılımcı
            'alumni',      // Mezun
            'guest'        // Ziyaretçi
        ];

        $roleObjects = [];
        foreach ($roles as $roleName) {
            $roleObjects[$roleName] = Role::firstOrCreate(['name' => $roleName, 'guard_name' => 'web']);
        }

        // 3. Rollere İzinleri Atama
        // Super admin tüm izinleri alır
        $roleObjects['super-admin']->syncPermissions(Permission::all());

        // Coordinator proje düzeyindeki tüm operasyonel izinleri alır
        $roleObjects['coordinator']->syncPermissions([
            'manage_project',
            'evaluate_applications',
            'take_attendance',
            'upload_materials',
            'send_sms_email',
            'manage_forum',
        ]);
        
        // Staff varsayılan olarak boş gelir, ara yüzden proje bazlı ekstra izin eklenecektir veya buraya varsayılan olarak eklenebilir
        // Öğrenci, mezun ve ziyaretçi rolleri sistemde sadece statü belirler, ekstra admin paneli izni almazlar.

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
