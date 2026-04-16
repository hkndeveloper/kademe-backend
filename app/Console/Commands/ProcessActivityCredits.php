<?php
namespace App\Console\Commands;

use App\Models\Activity;
use App\Models\Attendance;
use App\Models\ParticipantProfile;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ProcessActivityCredits extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'kademe:process-credits {activity_id?}';

    /**
     * The console command description.
     */
    protected $description = 'Biten faaliyetler için yoklama kontrolü yapar ve puan düşümünü gerçekleştirir.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $activityId = $this->argument('activity_id');

        $activities = Activity::query()
            ->when($activityId, fn($q) => $q->where('id', $activityId))
            ->where('end_time', '<', now())
            ->where('is_active', true)
            ->get();

        foreach ($activities as $activity) {
            $this->info("İşleniyor: {$activity->name}");

            // Bu projeye dahil olan tum ogrenciler (simdilik tum 'student' rolundekiler veya projeye atananlar)
            // Not: Gercek senaryoda Proje-Katilimci iliskisi (Pivot) olmali.
            // Simdilik tum ogrencileri bu projenin bir parcasi kabul ediyoruz (demo amacli).
            $participants = ParticipantProfile::where('status', 'active')->get();

            foreach ($participants as $participant) {
                // Bu aktivite icin yoklama kaydi var mi?
                $attendance = Attendance::where('user_id', $participant->user_id)
                    ->where('activity_id', $activity->id)
                    ->where('status', 'attended')
                    ->first();

                if (!$attendance) {
                    // Katilmamis! Kredi dus.
                    $loss = $activity->credit_loss_amount ?? 10;
                    
                    DB::transaction(function () use ($participant, $activity, $loss) {
                        $participant->decrement('credits', $loss);
                        
                        Attendance::create([
                            'user_id' => $participant->user_id,
                            'activity_id' => $activity->id,
                            'status' => 'missed',
                            'credit_impact' => -$loss
                        ]);
                    });

                    // Refresh model and check threshold (Section 6.3)
                    $participant->refresh();
                    if ($participant->credits < 75) {
                        // Kredi eşik değerin altına düştüğünde SMS at
                        $smsService = app(\App\Services\WebasistSmsService::class);
                        if ($participant->phone) {
                            $smsService->sendSms(
                                $participant->phone, 
                                "Sayın KADEME katılımcısı, krediniz 75'in altına düşmüştür. Bir sonraki faaliyete katılmazsanız sistemden çıkarılabilirsiniz."
                            );
                            $this->warn("Kritik eşik SMS'i gönderildi: {$participant->user_id}");
                        }
                    }

                    // Otomatik Kara Liste (Blacklist) Kontrolü (Section 14.1)
                    $missedCount = Attendance::where('user_id', $participant->user_id)
                        ->where('status', 'missed')
                        ->count();

                    if ($missedCount >= 3 && $participant->status !== 'blacklist') {
                        $participant->update(['status' => 'blacklist']);
                        $this->error("Kullanıcı Kara Listeye alındı: {$participant->user_id} (Bağlantısız devamsızlık: {$missedCount})");
                    }

                    $this->warn("Kredi dusuldu: {$participant->user_id} (-{$loss})");
                }
            }
            
            // Faaliyeti pasife cek veya islendigini isaretle ki tekrar calismasin
            $activity->update(['is_active' => false]);
            $this->info("Bitti: {$activity->name}");
        }

        return 0;
    }
}
