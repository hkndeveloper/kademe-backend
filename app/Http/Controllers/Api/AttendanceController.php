<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Activity;
use App\Models\Attendance;
use App\Models\ParticipantProfile;
use App\Models\User;
use App\Models\Badge;
use App\Services\CreditService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AttendanceController extends Controller
{
    protected $creditService;

    public function __construct(CreditService $creditService)
    {
        $this->creditService = $creditService;
    }

    /**
     * Yoklama verme işlemi
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'activity_id' => 'required|exists:activities,id',
            'qr_code_secret' => 'required|string',
            'latitude' => 'required|numeric',
            'longitude' => 'required|numeric',
        ]);

        $activity = Activity::findOrFail($validated['activity_id']);
        $user = Auth::user();

        // -1. Blacklist Kontrolü (Section 14.1)
        if ($user->participantProfile && $user->participantProfile->status === 'blacklisted') {
            return response()->json(['message' => 'Hesabınız askıya alınmıştır. Yoklama veremezsiniz.'], 403);
        }

        // 0. Geri Bildirim Kontrolü (Section 11.14)
        // Geçmişte katıldığı ama değerlendirme yapmadığı faaliyet var mı?
        $pendingFeedback = \Illuminate\Support\Facades\DB::table('attendances')
            ->join('activities', 'attendances.activity_id', '=', 'activities.id')
            ->leftJoin('feedback', function ($join) use ($user) {
                $join->on('activities.id', '=', 'feedback.activity_id')
                     ->where('feedback.user_id', '=', $user->id);
            })
            ->where('attendances.user_id', $user->id)
            ->where('attendances.status', 'attended')
            ->whereNull('feedback.id')
            ->select('activities.id as pending_activity_id', 'activities.name')
            ->first();

        if ($pendingFeedback && $pendingFeedback->pending_activity_id != $activity->id) {
            return response()->json([
                'message' => 'Lütfen önceki yoklamalarınız için değerlendirme formunu doldurun.',
                'require_feedback' => true,
                'pending_activity' => $pendingFeedback
            ], 403);
        }

        // 1. QR Kod Kontrolü (Dinamik Kontrol)
        $currentSecret = $activity->getDynamicQrSecret();
        $previousSecret = $activity->getDynamicQrSecret(time() - 30); // 30 saniye önceki kod da kabul edilir (gecikmeler için)
        
        if ($validated['qr_code_secret'] !== $currentSecret && $validated['qr_code_secret'] !== $previousSecret && $validated['qr_code_secret'] !== $activity->qr_code_secret) {
            return response()->json(['message' => 'Geçersiz veya süresi dolmuş QR kod. Lütfen ekranı yenileyin.'], 422);
        }

        // 2. Zaman Kontrolü (Faaliyet su an aktif mi?)
        $now = now();
        if ($now < $activity->start_time->subMinutes(30) || $now > $activity->end_time->addMinutes(30)) {
            return response()->json(['message' => 'Yoklama süresi dışında.'], 422);
        }

        // 3. Konum Kontrolü (Haversine Formülü)
        $distance = $this->calculateDistance(
            $validated['latitude'], $validated['longitude'],
            $activity->latitude, $activity->longitude
        );

        $locationVerified = $distance <= $activity->radius;

        if (!$locationVerified) {
            return response()->json([
                'message' => 'Konum doğrulanamadı. Faaliyet alanında olmalısınız.',
                'distance' => round($distance, 2) . ' metre'
            ], 422);
        }

        // 4. Yoklama Kaydı ve Kredi Güncelleme
        $attendance = Attendance::updateOrCreate(
            ['user_id' => $user->id, 'activity_id' => $activity->id],
            [
                'status' => 'attended',
                'location_verified' => true,
                'credit_impact' => 0 // Katilimda genellikle kredi dusmez, katilmama durumunda duser.
            ]
        );

        $this->checkBadges($user);

        // Mezuniyet Kontrolü (Section 9.1)
        app(\App\Services\GraduationService::class)->checkAndProcessGraduation($user);

        return response()->json([
            'message' => 'Yoklama başarıyla alındı. Teşekkürler!',
            'attendance' => $attendance
        ]);
    }

    /**
     * Koordinatörler için Manuel Yoklama Girişi (Section 11.6)
     */
    public function manualStore(Request $request)
    {
        $validated = $request->validate([
            'activity_id' => 'required|exists:activities,id',
            'user_id' => 'required|exists:users,id',
            'note' => 'nullable|string'
        ]);

        $user = User::findOrFail($validated['user_id']);
        $activity = Activity::findOrFail($validated['activity_id']);
        $admin = auth()->user();
        if ($admin) {
            $this->authorize('takeAttendance', $activity->project);
        }

        // Mevcut kaydı güncelle veya oluştur
        $attendance = Attendance::updateOrCreate(
            ['user_id' => $validated['user_id'], 'activity_id' => $validated['activity_id']],
            [
                'status' => 'attended',
                'location_verified' => true,
                'credit_impact' => 0,
                'note' => $validated['note'] ?? 'Manuel giriş yapıldı.'
            ]
        );

        $this->checkBadges($user);

        // Mezuniyet Kontrolü (Section 9.1)
        app(\App\Services\GraduationService::class)->checkAndProcessGraduation($user);

        return response()->json([
            'message' => 'Manuel yoklama başarıyla kaydedildi.',
            'attendance' => $attendance
        ]);
    }

    /**
     * Otomatik Rozet Kazanma Kontrolü (Section 8)
     */
    private function checkBadges(User $user)
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
     * Devamsızlık İşleme ve Kredi Düşümü (Section 6.3)
     * Manuel veya Scheduled tetiklenebilir.
     */
    public function processAbsences($activityId)
    {
        $activity = Activity::findOrFail($activityId);

        // Use CreditService to handle credit deduction and alerts
        $alertedUsers = $this->creditService->deductCreditsForMissedActivity($activity);

        // Bu faaliyete katılması beklenen (başvurusu kabul edilmiş) herkesi bul
        $expectedUserIds = \App\Models\Application::where('project_id', $activity->project_id)
            ->where('status', 'accepted')
            ->pluck('user_id');

        // Gelmeyenleri bul
        $attendedUserIds = Attendance::where('activity_id', $activityId)
            ->where('status', 'attended')
            ->pluck('user_id');

        $absentUserIds = $expectedUserIds->diff($attendedUserIds);

        foreach ($absentUserIds as $userId) {
            // Yoklama kaydını 'absent' olarak oluştur/güncelle
            Attendance::updateOrCreate(
                ['user_id' => $userId, 'activity_id' => $activityId],
                ['status' => 'absent', 'credit_impact' => -($activity->credit_loss_amount ?? 10)]
            );

            // Otomatik Kara Liste Mekanizması (Section 14.1)
            // 3 kez mazeretsiz katılmayanlar otomatik "Blacklist" statüsüne alınır.
            $absentCount = Attendance::where('user_id', $userId)->where('status', 'absent')->count();
            if ($absentCount >= 3) {
                $profile = ParticipantProfile::where('user_id', $userId)->first();
                if ($profile) {
                    $profile->update(['status' => 'blacklisted']);

                    $commService = app(\App\Services\CommunicationService::class);
                    $message = "SİSTEMDEN ÇIKARILDINIZ: 3 kez mazeretsiz devamsızlık yaptığınız için hesabınız kara listeye alınmıştır.";
                    
                    // SMS
                    $commService->sendSms($userId, $profile->phone, $message);

                    // Email
                    $commService->sendEmail(
                        $userId,
                        $user->email,
                        'Hesabınız Askıya Alındı (Kara Liste)',
                        "Merhaba {$user->name},\n\nDevamsızlık sınırını (3 kez) aştığınız için KADEME öğrenci profiliniz otomatik olarak 'Kara Liste' statüsüne alınmıştır. Mevcut programlara katılımınız durdurulmuş ve yeni başvuru haklarınız askıya alınmıştır.\n\nİtiraz veya bilgi için koordinatörlük ile iletişime geçebilirsiniz."
                    );
                }
            }
        }

        return response()->json([
            'message' => count($absentUserIds) . ' kişi devamsız olarak işaretlendi ve kredi düşümü yapıldı.',
            'alerted_users' => count($alertedUsers)
        ]);
    }

    /**
     * İki koordinat arası mesafeyi metre cinsinden hesaplar
     */
    private function calculateDistance($lat1, $lon1, $lat2, $lon2)
    {
        $earthRadius = 6371000; // Metre

        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);

        $a = sin($dLat / 2) * sin($dLat / 2) +
             cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
             sin($dLon / 2) * sin($dLon / 2);

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return $earthRadius * $c;
    }
}
