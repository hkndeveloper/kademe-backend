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

    public function __construct(GraduationService $graduationService)
    {
        $this->graduationService = $graduationService;
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
     * Devamsızlık İşleme ve Kredi Düşümü
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
            Attendance::updateOrCreate(
                ['user_id' => $userId, 'activity_id' => $activity->id],
                ['status' => 'missed', 'credit_impact' => -($activity->credit_loss_amount ?? 10)]
            );

            // Blacklist check
            $absentCount = Attendance::where('user_id', $userId)->where('status', 'missed')->count(); 
            if ($absentCount >= 3) {
                $profile = ParticipantProfile::where('user_id', $userId)->first();
                if ($profile && $profile->status !== 'blacklisted') {
                    $profile->update(['status' => 'blacklisted']);
                }
            }
        }

        return count($absentUserIds);
    }
}
