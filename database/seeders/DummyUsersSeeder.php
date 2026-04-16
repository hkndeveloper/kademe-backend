<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class DummyUsersSeeder extends Seeder
{
    public function run()
    {
        // Önce rollerin varlığından emin olalım
        $roles = ['super-admin', 'coordinator', 'student', 'alumni'];
        foreach ($roles as $roleName) {
            Role::firstOrCreate(['name' => $roleName, 'guard_name' => 'api']);
            Role::firstOrCreate(['name' => $roleName, 'guard_name' => 'web']);
        }

        $users = [
            [
                'name' => 'KADEME Üst Admin',
                'email' => 'admin@kademe.org',
                'role' => 'super-admin'
            ],
            [
                'name' => 'Proje Koordinatörü',
                'email' => 'coordinator@kademe.org',
                'role' => 'coordinator'
            ],
            [
                'name' => 'Örnek Öğrenci',
                'email' => 'student@kademe.org',
                'role' => 'student'
            ],
            [
                'name' => 'Başarılı Mezun',
                'email' => 'alumni@kademe.org',
                'role' => 'alumni'
            ],
        ];

        foreach ($users as $u) {
            $user = User::updateOrCreate(
                ['email' => $u['email']],
                [
                    'name' => $u['name'],
                    'password' => Hash::make('password123'),
                ]
            );
            
            // Mevcut tüm rolleri temizleyip yenisini atayalım (garanti olsun)
            $user->syncRoles([$u['role']]);

            if (in_array($u['role'], ['student', 'alumni'])) {
                \App\Models\ParticipantProfile::updateOrCreate(
                    ['user_id' => $user->id],
                    [
                        'phone' => '555000000' . $user->id,
                        'university' => 'Selçuk Üniversitesi',
                        'department' => 'Siyaset Bilimi',
                        'status' => $u['role'] === 'alumni' ? 'alumni' : 'active'
                    ]
                );
            }
        }
    }
}
