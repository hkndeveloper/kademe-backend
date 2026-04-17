<?php

namespace App\Services;

use App\Models\Attendance;
use App\Models\Badge;
use App\Models\User;
use App\Models\ParticipantProfile;
use App\Models\Activity;

class AttendanceService
{
    protected $graduationService;
    protected $communicationService;

    public function __construct(GraduationService $graduationService, CommunicationService $communicationService)
    {
        $this->graduationService = $graduationService;
        $this->communicationService = $communicationService;
    }

    /**
     * Record attendance and trigger side effects
     */
    public function recordAttendance(User $user, Activity $activity, array $data = [])
    {
        $attendance = Attendance::updateOrCreate(
            ['user_id' => $user->id, 'activity_id' => $activity->id],
            array_merge([
                'status' => 'attended',
                'location_verified' => true,
                'credit_impact' => 0
            ], $data)
        );

        // side effects
        $this->checkBadges($user);
        $this->graduationService->checkAndProcessGraduation($user);

        return $attendance;
    }

    /**
     * Otomatik Rozet Kazanma Kontrolü
     */
    public function checkBadges(User $user)
    {
        $attendanceCount = Attendance::where('user_id', $user->id)->where('status', 'attended')->count();

        // 1. İlk Adım Rozeti
        if ($attendanceCount >= 1) {
            $badge = Badge::where('name', 'İlk Adım')->first();
            if ($badge && !$user->badges()->where('badge_id', $badge->id)->exists()) {
                $user->badges()->attach($badge->id);
            }
        }

        // 2. Sadık Katılımcı Rozeti
        if ($attendanceCount >= 5) {
            $badge = Badge::where('name', 'Sadık Katılımcı')->first();
            if ($badge && !$user->badges()->where('badge_id', $badge->id)->exists()) {
                $user->badges()->attach($badge->id);
            }
        }
    }

    /**
     * Devamsızlık İşleme - Kredi Düşümü, Kara Liste ve SMS Yönetimi (Section 6.3 & 14.1)
     */
    public function processAbsences(Activity $activity)
    {
        $expectedUserIds = \App\Models\Application::where('project_id', $activity->project_id)
            ->where('status', 'accepted')
            ->pluck('user_id');

        $attendedUserIds = Attendance::where('activity_id', $activity->id)
            ->where('status', 'attended')
            ->pluck('user_id');

        $absentUserIds = $expectedUserIds->diff($attendedUserIds);

        foreach ($absentUserIds as $userId) {
            $creditLoss = $activity->credit_loss_amount ?? 10;

            Attendance::updateOrCreate(
                ['user_id' => $userId, 'activity_id' => $activity->id],
                ['status' => 'missed', 'credit_impact' => -$creditLoss]
            );

            // Kredi Güncelleme ve SMS Uyarıları
            $profile = ParticipantProfile::where('user_id', $userId)->first();
            if ($profile) {
                $oldCredits = $profile->credits;
                $newCredits = max(0, $oldCredits - $creditLoss);
                $profile->credits = $newCredits;
                $profile->save();

                // 1. Kredi Eşiği Uyarısı (Threshold 75)
                if ($oldCredits >= 75 && $newCredits < 75) {
                    $this->communicationService->sendSms(
                        $userId, 
                        $profile->phone, 
                        "KADEME UYARI: Mevcut krediniz 75'in altına düşmüştür. Devamsızlığınızın devam etmesi durumunda sistemden çıkarılma riskiyle karşı karşıyasınız.",
                        $activity->project_id
                    );
                }

                // 2. Kara Liste Kontrolü (3 Missed Session - Section 14.1)
                $absentCount = Attendance::where('user_id', $userId)->where('status', 'missed')->count(); 
                if ($absentCount >= 3 && $profile->status !== 'blacklisted') {
                    $profile->update([
                        'status' => 'blacklisted',
                        'blacklisted_at' => now(),
                        'blacklist_reason' => 'Mazeretsiz 3 kez devamsızlık yapıldı.'
                    ]);

                    // Kara listeye alınan kullanıcının tüm açık başvurularını ve kabullerini iptal et (Section 14.1)
                    \App\Models\Application::where('user_id', $userId)
                        ->whereIn('status', ['pending', 'accepted', 'waitlisted'])
                        ->update(['status' => 'rejected']);

                    $this->communicationService->sendSms(
                        $userId, 
                        $profile->phone, 
                        "KADEME BİLGİ: Mazeretsiz 3 kez devamsızlık yapmanız nedeniyle sistemimiz tarafından 'Kara Liste'ye alındınız. Mevcut tüm program katılımlarınız ve başvurularınız iptal edilmiştir.",
                        $activity->project_id
                    );
                }
            }
        }

        return count($absentUserIds);
    }
}
