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
        ]);

        $users = User::whereIn('id', $validated['user_ids'])->with('participantProfile')->get();
        $count = 0;

        foreach ($users as $user) {
            if ($validated['type'] === 'sms') {
                $phone = $user->participantProfile ? $user->participantProfile->phone : null;
                if ($phone) {
                    $this->commService->sendSms($user->id, $phone, $validated['content']);
                    $count++;
                }
            } else {
                $this->commService->sendEmail($user->id, $user->email, $validated['subject'] ?? 'KADEME Duyurusu', $validated['content']);
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
        $logs = \App\Models\CommunicationLog::with('user')->latest()->paginate(50);
        return response()->json($logs);
    }
}
