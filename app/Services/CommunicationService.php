<?php

namespace App\Services;

use App\Models\CommunicationLog;
use App\Mail\NotificationMail;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use App\Models\User;

class CommunicationService
{
    /**
     * SMS Gönderim Simülasyonu (Section 5.2)
     */
    public function sendSms($userId, $phone, $message)
    {
        // Webasist API entegrasyonu (Şartname 11.19)
        // Gerçek API anahtarlarını buraya ekleyebilirsiniz.
        
        Log::info("SMS to {$phone}: {$message}");

        return CommunicationLog::create([
            'user_id' => ($userId && $userId > 0) ? $userId : null,
            'type' => 'sms',
            'recipient' => $phone,
            'content' => $message,
            'status' => 'sent',
            'provider' => 'webasist'
        ]);
    }

    /**
     * Gerçek Email Gönderimi (Section 11.4)
     */
    public function sendEmail($userId, $email, $subject, $content)
    {
        try {
            $user = User::find($userId);
            $userName = $user ? $user->name : null;

            Mail::to($email)->send(new NotificationMail($subject, $content, $userName));

            return CommunicationLog::create([
                'user_id' => ($userId && $userId > 0) ? $userId : null,
                'type' => 'email',
                'recipient' => $email,
                'content' => "Subject: {$subject}\n\n{$content}",
                'status' => 'sent',
                'provider' => 'smtp'
            ]);
        } catch (\Exception $e) {
            Log::error("Email failed to {$email}: " . $e->getMessage());
            
            return CommunicationLog::create([
                'user_id' => ($userId && $userId > 0) ? $userId : null,
                'type' => 'email',
                'recipient' => $email,
                'content' => "Subject: {$subject}\n\n{$content}\n\nError: " . $e->getMessage(),
                'status' => 'failed',
                'provider' => 'smtp'
            ]);
        }
    }
}
