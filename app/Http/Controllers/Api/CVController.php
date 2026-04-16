<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\ParticipantProfile;
use App\Models\User;
use App\Models\Application;
use App\Models\Attendance;

class CVController extends Controller
{
    /**
     * Öğrencinin Kendi CV Verilerini Getirir
     */
    public function getMyCv()
    {
        $user = auth()->user()->load(['participantProfile', 'badges']);
        
        $history = $this->getUserProjectHistory($user->id);

        return response()->json([
            'profile' => $user,
            'history' => $history,
            'public_url' => url("/cv/" . $user->participantProfile->public_id)
        ]);
    }

    /**
     * Kamuya Açık Dijital CV Görüntüleme (Section 9.2)
     */
    public function show($publicId)
    {
        $profile = ParticipantProfile::where('public_id', $publicId)
            ->with(['user.badges'])
            ->firstOrFail();

        $user = $profile->user;
        $history = $this->getUserProjectHistory($user->id);

        // Toplam katılım sayısı
        $totalAttendance = Attendance::where('user_id', $user->id)->where('status', 'attended')->count();

        return response()->json([
            'name' => $user->name,
            'university' => $profile->university,
            'department' => $profile->department,
            'credits' => $profile->credits,
            'is_graduated' => $profile->is_graduated,
            'graduated_at' => $profile->graduated_at ? $profile->graduated_at->format('d.m.Y') : null,
            'certificate_id' => $profile->graduation_certificate_id,
            'badges' => $user->badges,
            'history' => $history,
            'stats' => [
                'total_attendance' => $totalAttendance,
                'completed_projects' => count($history->where('status', 'Tamamlandı')),
                'badges_count' => $user->badges->count()
            ],
            'verified_at' => $profile->updated_at->format('d.m.Y'),
            'qr_code' => "https://api.qrserver.com/v1/create-qr-code/?size=150x150&data=" . urlencode(url("/cv/{$publicId}"))
        ]);
    }

    private function getUserProjectHistory($userId)
    {
        return Application::where('user_id', $userId)
            ->whereIn('status', ['accepted', 'completed']) // 'completed' statüsü de eklenebilir
            ->with('project')
            ->get()
            ->map(function($app) {
                return [
                    'project_name' => $app->project->name,
                    'status' => $app->status === 'completed' ? 'Tamamlandı' : 'Aktif Katılımcı',
                    'date' => $app->created_at->format('M Y')
                ];
            });
    }

    /**
     * Öğrencinin Kendi CV Bilgilerini Güncellemesi
     */
    public function update(Request $request)
    {
        $user = auth()->user();
        $profile = $user->participantProfile;

        $request->validate([
            'university' => 'sometimes|string|max:255',
            'department' => 'sometimes|string|max:255',
        ]);

        $profile->update($request->only(['university', 'department']));

        return response()->json(['message' => 'CV bilgileriniz güncellendi.']);
    }
}
