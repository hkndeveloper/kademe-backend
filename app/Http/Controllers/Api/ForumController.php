<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ForumMessage;
use App\Models\Application;
use Illuminate\Http\Request;

class ForumController extends Controller
{
    /**
     * Projeye ait forum mesajlarını getirir
     */
    public function index(Request $request, $projectId)
    {
        $user = auth()->user();
        
        // Admin veya bu projeye kabul edilmiş bir öğrenci mi?
        $isAdmin = $user->hasAnyRole(['super-admin', 'coordinator']);
        $isAccepted = Application::where('user_id', $user->id)
            ->where('project_id', $projectId)
            ->where('status', 'accepted')
            ->exists();

        if (!$isAdmin && !$isAccepted) {
            return response()->json([
                'message' => 'Bu projenin forumuna erişim yetkiniz yok.',
                'user_roles' => $user->getRoleNames(),
                'project_id' => $projectId
            ], 403);
        }

        $messages = ForumMessage::with('user:id,name')
            ->where('project_id', $projectId)
            ->orderBy('created_at', 'asc')
            ->get();

        return response()->json($messages);
    }

    /**
     * Foruma yeni mesaj gönderir
     */
    public function store(Request $request, $projectId)
    {
        $request->validate([
            'message' => 'required|string|max:1000'
        ]);

        $user = auth()->user();
        
        $isAdmin = $user->hasAnyRole(['super-admin', 'coordinator']);
        $isAccepted = Application::where('user_id', $user->id)
            ->where('project_id', $projectId)
            ->where('status', 'accepted')
            ->exists();
  
        if (!$isAdmin && !$isAccepted) {
            return response()->json(['message' => 'Bu projenin forumuna mesaj gönderme yetkiniz yok.'], 403);
        }

        $message = ForumMessage::create([
            'project_id' => $projectId,
            'user_id' => $user->id,
            'message' => $request->message
        ]);

        return response()->json([
            'success' => true,
            'message' => $message->load('user:id,name')
        ], 201);
    }
}
