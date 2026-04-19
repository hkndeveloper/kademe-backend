<?php

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Seeder;

class SiteSettingsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $settings = [
            [
                'key' => 'mission_text',
                'value' => 'Katılımcılarımızın her faaliyetten somut yetkinlikler kazanarak ayrılmasını hedefliyoruz. Gönüllülük bilincini, akademik derinlik ve saha deneyimiyle birleştirerek "teoriden pratiğe" sarsılmaz bir köprü kuruyoruz.',
                'group' => 'about',
                'type' => 'string'
            ],
            [
                'key' => 'vision_text',
                'value' => 'Vizyonumuz, Türkiye\'nin en kapsamlı sivil toplum yönetim ağını kurarak, her gencin gelişimine dijital ve fiziksel altyapılarla sınırsız destek sunmaktır. Şeffaf, ölçülebilir ve etki odaklı projelerle geleceği şekillendiriyoruz.',
                'group' => 'about',
                'type' => 'string'
            ],
            [
                'key' => 'manual_stats_enabled',
                'value' => 'false',
                'group' => 'home',
                'type' => 'boolean'
            ],
            [
                'key' => 'manual_stats_json',
                'value' => json_encode([
                    'alumni_count' => '500+',
                    'active_projects' => '4',
                    'total_activities' => '12',
                    'satisfaction_rate' => '%94'
                ]),
                'group' => 'home',
                'type' => 'json'
            ],
            [
                'key' => 'insta_feed_json',
                'value' => json_encode([
                    ['id' => 1, 'url' => '#', 'image' => 'https://images.unsplash.com/photo-1523240715181-01491162e7fc?w=400&h=400&fit=crop'],
                    ['id' => 2, 'url' => '#', 'image' => 'https://images.unsplash.com/photo-1517486808906-6ca8b3f04846?w=400&h=400&fit=crop'],
                    ['id' => 3, 'url' => '#', 'image' => 'https://images.unsplash.com/photo-1522202176988-66273c2fd55f?w=400&h=400&fit=crop'],
                    ['id' => 4, 'url' => '#', 'image' => 'https://images.unsplash.com/photo-1531482615713-2afd69097998?w=400&h=400&fit=crop']
                ]),
                'group' => 'home',
                'type' => 'json'
            ],
        ];

        foreach ($settings as $setting) {
            Setting::updateOrCreate(['key' => $setting['key']], $setting);
        }
    }
}
