<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Project;
use App\Models\Activity;
use App\Models\Application;
use App\Models\Attendance;
use App\Models\Message;
use App\Models\ProjectMaterial;
use App\Models\Badge;
use Carbon\Carbon;
use Illuminate\Support\Facades\Hash;

class SystemShowcaseSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Projeler
        $projects = [
            [
                'name' => 'İdea-KOD Girişimcilik Programı',
                'slug' => 'idea-kod-girisimcilik',
                'description' => 'Gençlerin yazılım ve girişimcilik odaklı fikirlerini hayata geçirdiği 12 haftalık hızlandırma programı.',
                'sub_description' => 'Yazılım ve Girişimcilik Odaklı Fikir Hızlandırma',
                'is_active' => true,
                'is_pinned' => true,
                'application_deadline' => Carbon::now()->addDays(15),
                'format' => 'Hibrit',
                'period' => '2026 Bahar'
            ],
            [
                'name' => 'Liderlik ve Hitabet Atölyesi',
                'slug' => 'liderlik-ve-hitabet',
                'description' => 'KADEME bünyesinde gerçekleştirilen, etkili iletişim ve stratejik liderlik becerilerini geliştirmeyi hedefleyen atölye serisi.',
                'sub_description' => 'Etkili İletişim ve Liderlik Becerileri',
                'is_active' => true,
                'is_pinned' => true,
                'application_deadline' => Carbon::now()->addDays(7),
                'format' => 'Yüz yüze',
                'period' => '2026 Bahar'
            ]
        ];

        foreach ($projects as $p) {
            $project = Project::firstOrCreate(['name' => $p['name']], $p);

            // 2. Faaliyetler (Activities)
            $activities = [
                ['name' => 'Tanışma ve Oryantasyon', 'date' => now()->subDays(10)],
                ['name' => 'Stratejik Planlama Eğitimi', 'date' => now()->subDays(5)],
                ['name' => 'Canlı Yayın: Girişimci Buluşması', 'date' => now()->addDays(2)],
            ];

            foreach ($activities as $act) {
                Activity::firstOrCreate(['name' => $act['name'], 'project_id' => $project->id], [
                    'description' => $act['name'] . ' içeriği ve detayları.',
                    'start_time' => $act['date']->setHour(18),
                    'end_time' => $act['date']->setHour(20),
                    'room_name' => 'KADEME Genel Merkez - Salon A',
                    'latitude' => 41.0082,
                    'longitude' => 28.9784,
                    'radius' => 100,
                    'qr_code_secret' => bin2hex(random_bytes(5))
                ]);
            }

            // 3. Materyaller
            ProjectMaterial::create([
                'project_id' => $project->id,
                'uploaded_by' => 1,
                'title' => $project->name . ' Eğitim Kitapçığı',
                'description' => 'Program boyunca kullanılacak ders notları ve kaynakça.',
                'type' => 'document',
                'external_link' => 'https://example.com/kitapcik'
            ]);
        }

        // 4. Demo Öğrenci ve Veriler
        $student = User::firstOrCreate(
            ['email' => 'ogrenci@kademe.org'],
            [
                'name' => 'Ahmet Katılımcı',
                'password' => Hash::make('password'),
            ]
        );
        $student->assignRole('student');
        $student->participantProfile()->updateOrCreate([], [
            'university' => 'İstanbul Teknik Üniversitesi',
            'department' => 'Yazılım Mühendisliği',
            'tc_no' => '12345678901',
            'credits' => 95,
            'status' => 'active'
        ]);

        // Öğrenciyi Projeye Dahil Et
        $p1 = Project::first();
        Application::updateOrCreate(
            ['user_id' => $student->id, 'project_id' => $p1->id],
            ['status' => 'accepted', 'motivation_letter' => 'Kendimi geliştirmek istiyorum.']
        );

        // 5. Destek Mesajları
        Message::create([
            'sender_id' => $student->id,
            'receiver_id' => 1,
            'subject' => 'Sertifika hakkında bir sorum var',
            'body' => 'Merhabalar, program bittiğinde sertifikamı ne zaman alabilirim?',
            'type' => 'support'
        ]);

        // 6. Blog
        \Illuminate\Support\Facades\DB::table('posts')->insertOrIgnore([
            'title' => 'Geleceğin Liderleri KADEME\'de Yetişiyor',
            'slug' => 'gelecegin-liderleri',
            'content' => 'KADEME olarak gençlerimize sunduğumuz fırsatlar...',
            'author_id' => 1,
            'status' => 'published',
            'created_at' => now()
        ]);
    }
}
