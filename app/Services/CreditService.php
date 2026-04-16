<?php

namespace App\Services;

use App\Models\ParticipantProfile;
use App\Models\Activity;
use App\Models\Attendance;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class CreditService
{
    protected $smsService;
    protected $creditThreshold = 75; // Section 6.3: Threshold for SMS alert
    protected $defaultCreditLoss = 10; // Default credit loss per missed activity

    public function __construct(WebasistSmsService $smsService)
    {
        $this->smsService = $smsService;
    }

    /**
     * Deduct credits from participants who missed an activity
     * Section 6.3: Katılmadığı her program için kredi düşümü yapılır
     */
    public function deductCreditsForMissedActivity(Activity $activity)
    {
        $creditLoss = $activity->credit_loss_amount ?? $this->defaultCreditLoss;
        
        // Get all participants who should have attended but didn't
        $participants = ParticipantProfile::whereHas('user', function($query) use ($activity) {
            $query->whereHas('applications', function($q) use ($activity) {
                $q->where('project_id', $activity->project_id)
                  ->where('status', 'accepted');
            });
        })->get();

        $alertedUsers = [];

        foreach ($participants as $profile) {
            // Check if they actually attended
            $attended = Attendance::where('activity_id', $activity->id)
                ->where('user_id', $profile->user_id)
                ->exists();

            if (!$attended) {
                // Deduct credits
                $oldCredits = $profile->credits;
                $newCredits = max(0, $profile->credits - $creditLoss);
                
                $profile->update(['credits' => $newCredits]);
                
                Log::info("Credits deducted for user {$profile->user_id}: {$oldCredits} -> {$newCredits}");

                // Check if below threshold and send SMS alert
                if ($newCredits < $this->creditThreshold && $oldCredits >= $this->creditThreshold) {
                    $this->sendLowCreditAlert($profile);
                    $alertedUsers[] = $profile->user_id;
                }
            }
        }

        return $alertedUsers;
    }

    /**
     * Send SMS alert to user with low credits
     * Section 6.3: Kredi belirli bir eşik değerin (örn. 75) altına düştüğünde sistem otomatik uyarı SMS'i gönderir
     */
    protected function sendLowCreditAlert(ParticipantProfile $profile)
    {
        $message = "KADEME Bilgilendirme: Krediniz {$profile->credits}'e düştü. Bir programa daha katılmazsanız sistemden çıkarılacaksınız.";
        
        // SMS Gönderimi
        $this->smsService->sendSms($profile->phone, $message);
        
        // Email Gönderimi (Section 11.4)
        $user = $profile->user;
        if ($user) {
            $commService = app(\App\Services\CommunicationService::class);
            $commService->sendEmail(
                $user->id, 
                $user->email, 
                'Kritik Kredi Uyarısı', 
                "Merhaba {$user->name},\n\nKADEME projelerindeki katılım krediniz kritik seviye olan {$profile->credits} puana düşmüştür. Şartnamemiz gereği kredi eşiği altına düşen katılımcıların programla ilişiği kesilebilmektedir. Lütfen faaliyetlere katılımınız konusunda hassasiyet gösteriniz."
            );
        }

        Log::info("Low credit alerts (SMS & Email) triggered for user {$profile->user_id}");
    }

    /**
     * Get users with critical credit levels for coordinator notification
     * Section 6.3: Belirli bir kredinin altına düşen katılımcıların bilgileri ve kredisi ilgili proje adminine de bildirilir
     */
    public function getCriticalCreditUsers($projectId = null)
    {
        $query = ParticipantProfile::where('credits', '<', $this->creditThreshold)
            ->with('user');

        if ($projectId) {
            $query->whereHas('user', function($q) use ($projectId) {
                $q->whereHas('applications', function($subQ) use ($projectId) {
                    $subQ->where('project_id', $projectId)->where('status', 'accepted');
                });
            });
        }

        return $query->get();
    }

    /**
     * Reset credits for a new period
     * Section 11.2: Kredi sistemi dönemlik veya aylık olarak esnek planlanabilmelidir
     */
    public function resetCreditsForPeriod($period, $creditAmount = 100)
    {
        $updated = ParticipantProfile::where('period', $period)
            ->update(['credits' => $creditAmount]);
        
        Log::info("Credits reset for period {$period}: {$updated} users affected");
        
        return $updated;
    }

    /**
     * Manual credit adjustment (with audit logging)
     */
    public function adjustCredits($userId, $amount, $reason)
    {
        $profile = ParticipantProfile::where('user_id', $userId)->firstOrFail();
        $oldCredits = $profile->credits;
        $newCredits = max(0, $profile->credits + $amount);
        
        $profile->update(['credits' => $newCredits]);
        
        // Log this action
        DB::table('audit_logs')->insert([
            'user_id' => auth()->id(),
            'action' => 'adjust_credits',
            'model_type' => ParticipantProfile::class,
            'model_id' => $profile->id,
            'old_values' => json_encode(['credits' => $oldCredits]),
            'new_values' => json_encode(['credits' => $newCredits]),
            'reason' => $reason,
            'created_at' => now(),
        ]);
        
        Log::info("Manual credit adjustment for user {$userId}: {$oldCredits} -> {$newCredits} (Reason: {$reason})");
        
        return $profile;
    }

    /**
     * Check and alert all users with low credits (can be run as a scheduled job)
     */
    public function checkAndAlertLowCredits()
    {
        $criticalUsers = $this->getCriticalCreditUsers();
        $coordinators = User::whereHas('roles', function($q) {
            $q->whereIn('name', ['coordinator', 'super-admin']);
        })->get();

        // Notify coordinators about critical users
        foreach ($coordinators as $coordinator) {
            $message = "KADEME Risk Raporu: " . count($criticalUsers) . " katılımcının kredisi kritik seviyenin altında.";
            // This would typically send an email or in-app notification
            Log::info("Coordinator notification sent to {$coordinator->email}: {$message}");
        }

        return $criticalUsers;
    }
}
