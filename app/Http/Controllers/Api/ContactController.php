<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\CommunicationService;
use Illuminate\Support\Facades\Log;

class ContactController extends Controller
{
    protected $commService;

    public function __construct(CommunicationService $commService)
    {
        $this->commService = $commService;
    }

    /**
     * İletişim Formunu İşle (Section 2.1)
     */
    public function send(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email',
            'subject' => 'required|string|max:255',
            'message' => 'required|string|min:10'
        ]);

        try {
            // 1. Yöneticiye Bildirim Gönder (config üzerinden alıyoruz)
            $adminEmail = config('mail.from.address', 'admin@kademe.org');
            $this->commService->sendEmail(
                null,
                $adminEmail,
                "Yeni İletişim Formu Mesajı: " . $validated['subject'],
                "Gönderen: {$validated['name']} ({$validated['email']})\nKonu: {$validated['subject']}\n\nMesaj:\n{$validated['message']}"
            );

            // 2. Kullanıcıya Teşekkür Gönder
            $this->commService->sendEmail(
                null,
                $validated['email'],
                "Mesajınız Alındı - KADEME",
                "Merhaba {$validated['name']},\n\nİletişim formumuz üzerinden gönderdiğiniz mesaj başarıyla alınmıştır. En kısa sürede tarafınıza dönüş yapılacaktır.\n\nKADEME Yönetimi"
            );

            return response()->json([
                'message' => 'Mesajınız başarıyla gönderildi. Teşekkür ederiz.'
            ]);
        } catch (\Exception $e) {
            Log::error('Contact form error: ' . $e->getMessage());
            return response()->json([
                'message' => 'Mesaj gönderilirken bir hata oluştu. Lütfen daha sonra tekrar deneyin.'
            ], 500);
        }
    }
}
