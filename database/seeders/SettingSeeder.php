<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Setting;

class SettingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $settings = [
            // Kredi Sistemi Ayarları
            [
                'key' => 'default_starting_credits',
                'value' => '100',
                'group' => 'credits',
                'type' => 'integer'
            ],
            [
                'key' => 'credit_warning_threshold',
                'value' => '75',
                'group' => 'credits',
                'type' => 'integer'
            ],
            [
                'key' => 'default_credit_loss',
                'value' => '10',
                'group' => 'credits',
                'type' => 'integer'
            ],
            
            // Yoklama Ayarları
            [
                'key' => 'default_attendance_radius',
                'value' => '100',
                'group' => 'attendance',
                'type' => 'integer'
            ],
            [
                'key' => 'dynamic_qr_expiry_seconds',
                'value' => '30',
                'group' => 'attendance',
                'type' => 'integer'
            ],

            // Blacklist Ayarları
            [
                'key' => 'blacklist_missed_count_limit',
                'value' => '3',
                'group' => 'security',
                'type' => 'integer'
            ],
        ];

        foreach ($settings as $setting) {
            Setting::updateOrCreate(['key' => $setting['key']], $setting);
        }
    }
}
