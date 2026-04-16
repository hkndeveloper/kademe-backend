<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class RoleAndPermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Roller
        $roles = [
            'super-admin',
            'coordinator',
            'staff',
            'student',
            'guest'
        ];

        foreach ($roles as $role) {
            Role::firstOrCreate(['name' => $role, 'guard_name' => 'web']);
        }

        // İlk Super Admin (User tarafından verilen şifre ile aynı yapabiliriz veya güvenli bir şey)
        // Kullanıcı şifresi 12ytrmhkn_46'yı PG için vermişti. Admin için de benzerini kullanalım.
        $admin = User::updateOrCreate(
            ['email' => 'admin@kademe.org'],
            [
                'name' => 'KADEME Üst Admin',
                'password' => Hash::make('12ytrmhkn_46'),
            ]
        );

        $admin->assignRole('super-admin');
    }
}
