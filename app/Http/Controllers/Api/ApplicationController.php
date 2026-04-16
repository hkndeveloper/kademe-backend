<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Application;
use App\Models\Activity;
use App\Models\RejectionCriteria;
use App\Models\ParticipantProfile;
use Illuminate\Http\Request;
use App\Models\User;

class ApplicationController extends Controller
{
    // Katılımcının bir projeye başvurması (Saat çakışma kontrolü / Section 14.2)
    public function store(Request $request)
    {
        $request->validate([
            'project_id' => 'required|exists:projects,id',
            'motivation_letter' => 'nullable|string'
        ]);

        $user = auth()->user();

        // 1. Blacklist Kontrolü (Section 14.1)
        if ($user->participantProfile && $user->participantProfile->status === 'blacklisted') {
            return response()->json(['message' => 'Kara listede olduğunuz için yeni başvuru yapamazsınız.'], 403);
        }

        // 2. Daha evvel başvurmuş mu?
        if (Application::where('user_id', $user->id)->where('project_id', $request->project_id)->exists()) {
            return response()->json(['message' => 'Bu projeye zaten başvurdunuz.'], 400);
        }

        // 3. Otomatik Eleme Kriterleri Kontrolü (Section 11.9)
        $criteria = RejectionCriteria::where('project_id', $request->project_id)
            ->where('is_active', true)
            ->get();

        if ($criteria->count() > 0) {
            $profile = ParticipantProfile::where('user_id', $user->id)->first();

            if ($profile) {
                foreach ($criteria as $criterion) {
                    if (!$criterion->checkCriteria($profile)) {
                        return response()->json([
                            'message' => $criterion->rejection_message ?? 'Başvurunuz kriterlere uymadığı için reddedildi.',
                            'criteria_type' => $criterion->criteria_type
                        ], 422);
                    }
                }
            }
        }

        // 4. Çakışma Kontrol Sistemi (Section 14.2)
        $newProjectActivities = Activity::where('project_id', $request->project_id)->get();
        $userExistingActivities = Activity::whereIn('project_id', function($query) use ($user) {
            $query->select('project_id')->from('applications')->where('user_id', $user->id)->where('status', 'accepted');
        })->get();

        foreach ($newProjectActivities as $newAct) {
            foreach ($userExistingActivities as $extAct) {
                // Saat çakışması kontrolü
                if (
                    ($newAct->start_time >= $extAct->start_time && $newAct->start_time < $extAct->end_time) ||
                    ($newAct->end_time > $extAct->start_time && $newAct->end_time <= $extAct->end_time)
                ) {
                    return response()->json([
                        'message' => 'Saat çakışması bulunmaktadır.',
                        'conflict' => "{$newAct->name} faaliyeti, mevcut {$extAct->name} ile çakışıyor."
                    ], 422);
                }
            }
        }
        
        $application = Application::create([
            'user_id' => $user->id,
            'project_id' => $request->project_id,
            'motivation_letter' => $request->motivation_letter,
            'status' => 'pending'
        ]);

        // Başvuru Bilgilendirme Maili
        $commService = app(\App\Services\CommunicationService::class);
        $commService->sendEmail(
            $user->id, 
            $user->email, 
            'Başvurunuz Alındı', 
            "Merhaba {$user->name},\n\n{$application->project->name} için yaptığınız başvuru sisteme kaydedilmiştir. Değerlendirme süreci sonunda size tekrar bilgi verilecektir."
        );

        return response()->json(['message' => 'Başvuru başarıyla alındı.', 'application' => $application]);
    }

    // Adminin başvuruları listelemesi
    public function index(Request $request)
    {
        $user = auth()->user();
        $query = Application::with(['user.participantProfile', 'project']);

        if ($user && $user->hasRole('coordinator') && !$user->hasRole('super-admin')) {
            $query->whereIn('project_id', $user->coordinatedProjects->pluck('id'));
        }

        $applications = $query->latest()->get();
        return response()->json($applications);
    }

    // Adminin başvuruyu kabul/red/yedek etmesi
    public function updateStatus(Request $request, Application $application)
    {
        $user = auth()->user();
        if ($user) {
            $this->authorize('evaluateApplications', [App\Models\Project::class, $application->project]);
        }

        $request->validate([
            'status' => 'required|in:accepted,rejected,waitlisted'
        ]);

        $oldStatus = $application->status;
        
        // Veritabanini guncelle
        $application->status = $request->status;
        $application->save();

        // Slot açıldıysa (Accepted'dan başkasına geçtiyse) yedektekini çağır (Section 11.4)
        if ($oldStatus === 'accepted' && $request->status !== 'accepted') {
            try {
                $this->inviteNextFromWaitlist($application->project_id);
            } catch (\Exception $e) {
                \Log::error("Waitlist invitation error: " . $e->getMessage());
            }
        }

        if ($request->status === 'accepted') {
            // Ziyaretçiyi otomatik olarak 'Student' rolüne geçir (Section 11)
            $user = User::findOrFail($application->user_id);
            if (!$user->hasRole('student')) {
                $user->removeRole('guest');
                $user->assignRole('student');
                
                // Profil dönüşümü: Pasiften Aktife geçiş ve 100 kredi ataması
                \App\Models\ParticipantProfile::updateOrCreate(
                    ['user_id' => $user->id],
                    ['credits' => 100, 'status' => 'active']
                );
            }
        }

        try {
            // Durum Bildirim Maili
            $user = User::find($application->user_id);
            $commService = app(\App\Services\CommunicationService::class);
            $subject = 'Başvuru Sonucunuz Hakkında';
            $content = "Merhaba {$user->name},\n\n{$application->project->name} başvurunuz değerlendirilmiş ve durumu '" . strtoupper($request->status) . "' olarak güncellenmiştir.\n\nDetaylar için sisteme giriş yapabilirsiniz.";
            
            $commService->sendEmail($user->id, $user->email, $subject, $content);
        } catch (\Exception $e) {
            \Log::error("Application status notification failed: " . $e->getMessage());
        }

        return response()->json([
            'message' => 'Başvuru güncellendi.', 
            'application' => $application,
            'slot_opened' => ($oldStatus === 'accepted' && $request->status !== 'accepted')
        ]);
    }

    /**
     * Yedek Listesinden Sıradakini Davet Et (Section 11.15)
     */
    public function inviteNextFromWaitlist($projectId)
    {
        // En eski yedek başvuruyu bul (Şartnamede admin sıralayabilir diyor, şimdilik kronolojik)
        $nextInLine = Application::where('project_id', $projectId)
            ->where('status', 'waitlisted')
            ->orderBy('created_at', 'asc')
            ->first();

        if ($nextInLine) {
            $nextInLine->update(['status' => 'accepted']);
            
            // Profil dönüşümü
            $user = User::find($nextInLine->user_id);
            if (!$user->hasRole('student')) {
                $user->assignRole('student');
                \App\Models\ParticipantProfile::firstOrCreate(
                    ['user_id' => $user->id],
                    ['credits' => 100, 'status' => 'active']
                );
            }

            // SMS Bilgilendirme Simülasyonu
            $commService = app(\App\Services\CommunicationService::class);
            $profile = $user->participantProfile;
            if ($profile && $profile->phone) {
                $commService->sendSms(
                    $user->id, 
                    $profile->phone, 
                    "TEBRİKLER: Yedek listeden asil listeye geçtiniz! Programa katılımınız onaylandı."
                );
            }

            return $nextInLine;
        }

        return null;
    }
}
