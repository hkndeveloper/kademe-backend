<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Activity;
use App\Models\Attendance;
use App\Models\ParticipantProfile;
use App\Models\User;
use App\Models\Badge;
use App\Services\CreditService;
use App\Services\AttendanceService;
use App\Services\LocationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AttendanceController extends Controller
{
    protected $creditService;
    protected $attendanceService;
    protected $locationService;

    public function __construct(
        CreditService $creditService, 
        AttendanceService $attendanceService,
        LocationService $locationService
    ) {
        $this->creditService = $creditService;
        $this->attendanceService = $attendanceService;
        $this->locationService = $locationService;
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

        // 3. Konum Kontrolü
        $locationVerified = $this->locationService->isWithinRadius(
            $validated['latitude'], $validated['longitude'],
            $activity->latitude, $activity->longitude,
            $activity->radius
        );

        if (!$locationVerified) {
            return response()->json([
                'message' => 'Konum doğrulanamadı. Faaliyet alanında olmalısınız.'
            ], 422);
        }

        // 4. Yoklama Kaydı (Service üzerinden merkezi yönetim)
        $attendance = $this->attendanceService->recordAttendance($user, $activity);

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

        // Mevcut kaydı güncelle veya oluştur (Service üzerinden)
        $attendance = $this->attendanceService->recordAttendance($user, $activity, [
            'note' => $validated['note'] ?? 'Manuel giriş yapıldı.'
        ]);

        return response()->json([
            'message' => 'Manuel yoklama başarıyla kaydedildi.',
            'attendance' => $attendance
        ]);
    }

    /**
     * Devamsızlık İşleme (Service üzerinden)
     */
    public function processAbsences($activityId)
    {
        $activity = Activity::findOrFail($activityId);
        $absentCount = $this->attendanceService->processAbsences($activity);
        
        return response()->json([
            'message' => $absentCount . ' kişi devamsız olarak işaretlendi.'
        ]);
    }
}
