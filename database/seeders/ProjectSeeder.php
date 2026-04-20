<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Project;
use App\Models\Activity;

class ProjectSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. Diplomasi360
        $d360 = Project::create([
            'name' => 'Diplomasi360',
            'slug' => 'diplomasi360',
            'project_code' => 'D360',
            'description' => 'Küresel meseleleri ve diplomasi dünyasını 360 derece perspektiften inceleyen prestijli bir eğitim programıdır.',
            'is_active' => true,
        ]);

        // 2. KADEME+
        $kp = Project::create([
            'name' => 'KADEME+',
            'slug' => 'kademe-plus',
            'project_code' => 'KP-01',
            'description' => 'Gençlerin kariyer gelişimlerini destekleyen, profesyonel yetkinlik odaklı gelişim programı.',
            'is_active' => true,
        ]);

        // 3. Eurodesk
        $ed = Project::create([
            'name' => 'Eurodesk',
            'slug' => 'eurodesk',
            'project_code' => 'ED-TR',
            'description' => 'Avrupa fırsatları, gençlik hareketliliği ve projeleri hakkında bilgilendirme sunan resmi temas noktası.',
            'is_active' => true,
        ]);

        // Örnek Faaliyetler ekleyelim
        Activity::create([
            'project_id' => $d360->id,
            'name' => 'Diplomasiye Giriş ve Protokol Kuralları',
            'type' => 'training',
            'start_time' => now()->addDays(2)->setTime(10, 0),
            'end_time' => now()->addDays(2)->setTime(13, 0),
            'latitude' => 39.9334,
            'longitude' => 32.8597,
            'radius' => 100,
            'credit_loss_amount' => 15
        ]);

        Activity::create([
            'project_id' => $kp->id,
            'name' => 'CV Hazırlama ve Mülakat Teknikleri',
            'type' => 'event',
            'start_time' => now()->addDays(5)->setTime(14, 0),
            'end_time' => now()->addDays(5)->setTime(17, 0),
            'latitude' => 39.9334,
            'longitude' => 32.8597,
            'radius' => 100,
            'credit_loss_amount' => 10
        ]);
    }
}
