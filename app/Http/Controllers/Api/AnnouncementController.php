<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\CommunicationService;
use Illuminate\Http\Request;

class AnnouncementController extends Controller
{
    protected $commService;

    public function __construct(CommunicationService $commService)
    {
        $this->commService = $commService;
    }

    /**
     * Toplu SMS veya Email Gönderimi (Section 5.2, 11.4)
     */
    public function bulkSend(Request $request)
    {
        $validated = $request->validate([
            'type' => 'required|in:sms,email',
            'user_ids' => 'required|array',
            'user_ids.*' => 'exists:users,id',
            'subject' => 'required_if:type,email|string|max:255',
            'content' => 'required|string',
            'project_id' => 'nullable|exists:projects,id'
        ]);

        $user = auth()->user();
        $isCoordinator = $user->hasRole('coordinator') && !$user->hasRole('super-admin');
        
        $query = User::whereIn('id', $validated['user_ids'])->with('participantProfile');

        // Güvenlik Kilidi (Madde 5.2): Koordinatör sadece kendi projelerindeki öğrencilere mesaj atabilir.
        if ($isCoordinator) {
            $coordinatedProjectIds = $user->coordinatedProjects->pluck('id');
            $query->whereHas('applications', function($q) use ($coordinatedProjectIds) {
                $q->whereIn('project_id', $coordinatedProjectIds);
            });
        }

        $users = $query->get();
        
        if ($users->count() === 0) {
             return response()->json(['message' => 'Geçerli alıcı bulunamadı veya bu kişilere mesaj atma yetkiniz yok.'], 403);
        }

        $count = 0;
        $projectId = $validated['project_id'] ?? null;

        foreach ($users as $user) {
            if ($validated['type'] === 'sms') {
                $phone = $user->participantProfile ? $user->participantProfile->phone : null;
                if ($phone) {
                    $this->commService->sendSms($user->id, $phone, $validated['content'], $projectId);
                    $count++;
                }
            } else {
                $this->commService->sendEmail($user->id, $user->email, $validated['subject'] ?? 'KADEME Duyurusu', $validated['content'], $projectId);
                $count++;
            }
        }

        return response()->json([
            'message' => "{$count} kişiye duyuru başarıyla iletildi.",
            'sent_count' => $count
        ]);
    }
    
    /**
     * İletişim Loglarını Getirir (Section 11.5)
     */
    public function getLogs()
    {
        $user = auth()->user();
        $query = \App\Models\CommunicationLog::with('user');

        if ($user && $user->hasRole('coordinator') && !$user->hasRole('super-admin')) {
            $query->whereIn('project_id', $user->coordinatedProjects->pluck('id'));
        }

        return response()->json($query->latest()->paginate(50));
    }
}
