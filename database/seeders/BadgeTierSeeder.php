<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class BadgeTierSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $tiers = [
            [
                'name' => 'Gümüş Rozet',
                'min_badges' => 5,
                'title' => 'Gümüş Katılımcı',
                'frame_color' => '#94a3b8', // Silver/Slate
                'reward_description' => 'KADEME Kupa Seti'
            ],
            [
                'name' => 'Altın Rozet',
                'min_badges' => 10,
                'title' => 'Altın Lider',
                'frame_color' => '#fbbf24', // Gold
                'reward_description' => 'Liderlik Kampı Katılımı'
            ],
            [
                'name' => 'Platin Rozet',
                'min_badges' => 15,
                'title' => 'Platin Uzman',
                'frame_color' => '#60a5fa', // Blue/Platinum
                'reward_description' => 'Teknik Gezi Fırsatı'
            ],
            [
                'name' => 'Elmas Seviye',
                'min_badges' => 25,
                'title' => 'Elmas Elit',
                'frame_color' => '#c084fc', // Purple/Diamond
                'reward_description' => 'Mentorluk & Staj Garantisi'
            ],
        ];

        foreach ($tiers as $tier) {
            \App\Models\BadgeTier::updateOrCreate(['name' => $tier['name']], $tier);
        }
    }
}
