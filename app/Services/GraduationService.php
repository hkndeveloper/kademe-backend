<?php

namespace App\Services;

use App\Models\User;
use App\Models\Attendance;
use App\Models\Application;
use Illuminate\Support\Facades\Log;

class GraduationService
{
    /**
     * Kullanıcının mezuniyet durumunu kontrol eder ve gerekiyorsa mezun eder.
     * Kriterler (Section 9.1):
     * 1. Toplam Kredi > 300
     * 2. En az 2 projeyi başarıyla tamamlamış olmak (accepted başvurusu olan ve attendances olan)
     */
    public function checkAndProcessGraduation(User $user)
    {
        $profile = $user->participantProfile;
        if (!$profile || $profile->is_graduated) {
            return false;
        }

        // 1. Kredi Kontrolü
        if ($profile->credits < 300) {
            return false;
        }

        // 2. Tamamlanan Proje Kontrolü
        // 'accepted' durumdaki başvurularından en az 2 tanesinde faaliyetlerin %80'ine katılmış mı?
        $completedProjectsCount = 0;
        $acceptedApplications = Application::where('user_id', $user->id)
            ->where('status', 'accepted')
            ->with('project.activities')
            ->get();

        foreach ($acceptedApplications as $app) {
            $totalActivities = $app->project->activities->count();
            if ($totalActivities === 0) continue;

            $attendedCount = Attendance::where('user_id', $user->id)
                ->whereIn('activity_id', $app->project->activities->pluck('id'))
                ->where('status', 'attended')
                ->count();

            $attendanceRate = ($attendedCount / $totalActivities) * 100;

            if ($attendanceRate >= 80) {
                $completedProjectsCount++;
            }
        }

        if ($completedProjectsCount >= 2) {
            return $this->graduateUser($user);
        }

        return false;
    }

    private function graduateUser(User $user)
    {
        try {
            $profile = $user->participantProfile;
            $profile->update([
                'is_graduated' => true,
                'graduated_at' => now(),
                'graduation_certificate_id' => 'KDM-' . strtoupper(bin2hex(random_bytes(4)))
            ]);

            // Mezun Rolü ata (Eğer varsa)
            if (!$user->hasRole('alumni')) {
                $user->assignRole('alumni');
            }

            // Tebrik Maili
            $commService = app(CommunicationService::class);
            $commService->sendEmail(
                $user->id,
                $user->email,
                'TEBRİKLER: KADEME Mezuniyetiniz Onaylandı!',
                "Merhaba {$user->name},\n\nKADEME Kurumsal Akademik Eğitim ve Mezuniyet Ekosistemi kapsamında gerekli kredi ve proje katılım şartlarını başarıyla tamamlayarak MEZUN olmaya hak kazandınız.\n\nDijital sertifikanız ve CV'niz profilinize eklenmiştir. Başarılarınızın devamını dileriz.\n\nKADEME Yönetimi"
            );

            return true;
        } catch (\Exception $e) {
            Log::error('Graduation error: ' . $e->getMessage());
            return false;
        }
    }
}
