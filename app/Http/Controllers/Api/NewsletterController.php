<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Newsletter;
use Illuminate\Http\Request;

class NewsletterController extends Controller
{
    /**
     * Subscribe to newsletter (Section 11.19)
     */
    public function subscribe(Request $request)
    {
        $request->validate([
            'email' => 'required|email|unique:newsletters',
            'name' => 'nullable|string|max:255'
        ]);

        $newsletter = Newsletter::create([
            'email' => $request->email,
            'name' => $request->name,
            'is_active' => true,
            'subscribed_at' => now()
        ]);

        return response()->json([
            'message' => 'Bültene başarıyla abone oldunuz.',
            'newsletter' => $newsletter
        ], 201);
    }

    /**
     * Unsubscribe from newsletter
     */
    public function unsubscribe($email)
    {
        $newsletter = Newsletter::where('email', $email)->firstOrFail();
        $newsletter->update(['is_active' => false]);

        return response()->json(['message' => 'Bültenden abonelik iptal edildi.']);
    }

    /**
     * Get all subscribers (admin only)
     */
    public function index()
    {
        $subscribers = Newsletter::where('is_active', true)->get();
        return response()->json($subscribers);
    }

    /**
     * Send newsletter to all subscribers (Section 11.13)
     */
    public function send(Request $request)
    {
        $request->validate([
            'subject' => 'required|string',
            'content' => 'required|string'
        ]);

        $subscribers = Newsletter::where('is_active', true)->get();
        $commService = app(\App\Services\CommunicationService::class);
        
        $successCount = 0;
        foreach ($subscribers as $subscriber) {
            try {
                $commService->sendEmail(
                    null, // User ID yok bülten aboneleri için
                    $subscriber->email,
                    $request->subject,
                    $request->content
                );
                $successCount++;
            } catch (\Exception $e) {
                \DB::table('communication_logs')->insert([
                    'type' => 'email',
                    'recipient' => $subscriber->email,
                    'content' => $request->subject,
                    'status' => 'failed',
                    'provider' => 'smtp',
                    'created_at' => now(),
                    'updated_at' => now()
                ]);
            }
        }

        return response()->json([
            'message' => 'Bülten ' . $successCount . ' aboneye başarıyla gönderildi.',
            'subscribers_count' => $subscribers->count(),
            'success_count' => $successCount
        ]);
    }
}
