<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Message;
use App\Models\User;

class SupportController extends Controller
{
    /**
     * Gelen mesajları listele (Admin için tümü, Öğrenci için sadece kendisiyle ilgili)
     */
    public function index()
    {
        $user = auth()->user();
        
        if ($user->hasRole('super-admin') || $user->hasRole('admin')) {
            // Admin tüm ana mesajları (parent_id null olanları) görür
            $messages = Message::whereNull('parent_id')
                ->with(['sender:id,name,email'])
                ->latest()
                ->paginate(15);
        } else {
            // Öğrenci kendi başlattığı mesajları ve kendisine gelenleri görür
            $messages = Message::where(function($q) use ($user) {
                    $q->where('sender_id', $user->id)
                      ->orWhere('receiver_id', $user->id);
                })
                ->whereNull('parent_id')
                ->with(['sender:id,name', 'receiver:id,name'])
                ->latest()
                ->paginate(15);
        }

        return response()->json($messages);
    }

    /**
     * Mesaj detayını (konuşma geçmişini) getir
     */
    public function show($id)
    {
        $user = auth()->user();
        $mainMessage = Message::findOrFail($id);

        // Güvenlik kontrolü
        if (!$user->hasRole('super-admin') && $mainMessage->sender_id !== $user->id && $mainMessage->receiver_id !== $user->id) {
            return response()->json(['message' => 'Bu konuşmaya erişim yetkiniz yok.'], 403);
        }

        $thread = Message::where('id', $id)
            ->orWhere('parent_id', $id)
            ->with('sender:id,name')
            ->orderBy('created_at', 'asc')
            ->get();

        return response()->json($thread);
    }

    /**
     * Yeni destek talebi oluştur (Öğrenci -> Admin)
     */
    public function store(Request $request)
    {
        $request->validate([
            'subject' => 'required|string|max:255',
            'body' => 'required|string',
        ]);

        // Şimdilik tüm destek mesajları Üst Admin'e (ID 1) gitsin
        $admin = User::role('super-admin')->first();

        $message = Message::create([
            'sender_id' => auth()->id(),
            'receiver_id' => $admin ? $admin->id : 1,
            'subject' => $request->subject,
            'body' => $request->body,
            'type' => 'support'
        ]);

        return response()->json(['message' => 'Destek talebiniz iletildi.', 'data' => $message], 201);
    }

    /**
     * Mesaja cevap ver
     */
    public function reply(Request $request, $id)
    {
        $request->validate(['body' => 'required|string']);
        
        $parent = Message::findOrFail($id);
        $user = auth()->user();

        // Alıcı, parent mesajdaki diğer kişidir
        $receiverId = ($parent->sender_id === $user->id) ? $parent->receiver_id : $parent->sender_id;

        $reply = Message::create([
            'sender_id' => $user->id,
            'receiver_id' => $receiverId,
            'parent_id' => $parent->id,
            'body' => $request->body,
            'type' => 'support'
        ]);

        return response()->json(['message' => 'Cevabınız gönderildi.', 'data' => $reply], 201);
    }
}
