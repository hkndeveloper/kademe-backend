<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Project;
use App\Models\Activity;
use App\Models\Application;
use App\Models\ParticipantProfile;
use App\Models\AuditLog;
use App\Models\Message;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class BulkDataSeeder extends Seeder
{
    public function run(): void
    {
        $universities = ['İstanbul Teknik Üniversitesi', 'Ortadoğu Teknik Üniversitesi', 'Boğaziçi Üniversitesi', 'Yıldız Teknik Üniversitesi', 'Marmara Üniversitesi', 'Hacettepe Üniversitesi'];
        $departments = ['Bilgisayar Mühendisliği', 'İşletme', 'İktisat', 'Siyaset Bilimi', 'Uluslararası İlişkiler', 'Psikoloji'];
        $statuses = ['pending', 'accepted', 'rejected'];
        
        $projects = Project::all();
        if ($projects->isEmpty()) return;

        // 1. Koordinatörler Ekle (6 Adet)
        for ($i = 1; $i <= 6; $i++) {
            $user = User::firstOrCreate(
                ['email' => "koordinator{$i}@kademe.org"],
                [
                    'name' => "Koordinatör " . $i,
                    'password' => Hash::make('password123'),
                ]
            );
            $user->syncRoles(['coordinator']);
            
            // Rastgele bir projeye koordinatör ata (pivot tabloda tekrardan kaçınmak için sync kullanmıyoruz, ama kontrol ekliyoruz)
            if ($user->coordinatedProjects()->count() == 0) {
                $user->coordinatedProjects()->attach($projects->random()->id);
            }
        }

        // 2. Öğrenciler ve Profiller Ekle (40 Adet)
        for ($i = 1; $i <= 40; $i++) {
            $user = User::firstOrCreate(
                ['email' => "katilimci{$i}@example.com"],
                [
                    'name' => "Katılımcı " . $i,
                    'password' => Hash::make('password123'),
                ]
            );
            $user->syncRoles(['student']);

            ParticipantProfile::updateOrCreate(
                ['user_id' => $user->id],
                [
                    'tc_no' => '100000000' . str_pad($i, 2, '0', STR_PAD_LEFT),
                    'phone' => '55500000' . str_pad($i, 2, '0', STR_PAD_LEFT),
                    'university' => $universities[array_rand($universities)],
                    'department' => $departments[array_rand($departments)],
                    'credits' => rand(10, 200),
                    'status' => 'active',
                ]
            );

            // Rastgele 1-2 projeye başvur
            if ($user->applications()->count() == 0) {
                $appliedProjects = $projects->random(rand(1, 2));
                foreach ($appliedProjects as $project) {
                    Application::create([
                        'user_id' => $user->id,
                        'project_id' => $project->id,
                        'status' => $statuses[array_rand($statuses)],
                        'motivation_letter' => "Bu programa katılmayı çok istiyorum çünkü " . Str::random(20),
                    ]);
                }
            }
        }

        // 3. Ekstra Faaliyetler
        foreach ($projects as $project) {
            for ($j = 1; $j <= 3; $j++) {
                Activity::create([
                    'project_id' => $project->id,
                    'name' => $project->name . " - Oturum " . $j,
                    'description' => "Oturum detayları ve içerik planı.",
                    'start_time' => now()->addDays(rand(1, 30))->setHour(18),
                    'end_time' => now()->addDays(rand(1, 30))->setHour(20),
                    'room_name' => 'Online / Zoom',
                ]);
            }
        }

        // 4. Destek Mesajları
        for ($k = 1; $k <= 15; $k++) {
            Message::create([
                'sender_id' => User::role('student')->get()->random()->id,
                'receiver_id' => 1, // Admin
                'subject' => "Yardım Talebi #" . $k,
                'body' => "Sistem kullanımı hakkında bir sorum olacaktı.",
                'type' => 'support',
            ]);
        }

        // 5. Audit Logları
        for ($l = 1; $l <= 20; $l++) {
            AuditLog::create([
                'user_id' => 1,
                'action' => 'update_status',
                'target_type' => 'Application',
                'target_id' => $l,
                'description' => "Başvuru durumu güncellendi.",
                'ip_address' => '127.0.0.1',
            ]);
        }
    }
}
