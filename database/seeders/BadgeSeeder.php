<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class BadgeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $badges = [
            [
                'name' => 'İlk Adım',
                'description' => 'İlk faaliyetine katılarak KADEME ekosistemine giriş yaptın.',
                'icon' => 'Flag',
            ],
            [
                'name' => 'Sadık Katılımcı',
                'description' => '5 farklı faaliyete katılarak istikrarını kanıtladın.',
                'icon' => 'Heart',
            ],
            [
                'name' => 'Akademisyen',
                'description' => 'Bir projenin tüm eğitimlerini başarıyla tamamladın.',
                'icon' => 'BookOpen',
            ],
            [
                'name' => 'Dakik',
                'description' => 'Tüm yoklamalarını vaktinde verdin.',
                'icon' => 'Clock',
            ],
        ];

        foreach ($badges as $badge) {
            \App\Models\Badge::firstOrCreate(['name' => $badge['name']], $badge);
        }
    }
}
