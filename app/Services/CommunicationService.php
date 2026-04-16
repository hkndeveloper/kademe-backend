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
    public function sendSms($userId, $phone, $message, $projectId = null)
    {
        $apiKey = env('WEBASIST_API_KEY');
        $apiSecret = env('WEBASIST_API_SECRET');
        $senderName = env('WEBASIST_SENDER_NAME', 'KADEME');
        $url = env('WEBASIST_SMS_URL', 'https://api.webasist.com.tr/sms/send');

        // Eğer sistemde API key girilmemişse Development mantığıyla sadece Log at (Para gitmesin diye)
        if (!$apiKey) {
            Log::info("[MOCK SMS] to {$phone} (Project: {$projectId}): {$message}");
            return CommunicationLog::create([
                'user_id' => ($userId && $userId > 0) ? $userId : null,
                'project_id' => $projectId,
                'type' => 'sms',
                'recipient' => $phone,
                'content' => "[MOCK] " . $message,
                'status' => 'sent',
                'provider' => 'webasist'
            ]);
        }

        try {
            // Gerçek API İsteği (Webasist Dokümantasyonuna Göre HTTP POST)
            $response = \Illuminate\Support\Facades\Http::timeout(10)->post($url, [
                'api_key' => $apiKey,
                'api_secret' => $apiSecret,
                'sender' => $senderName,
                'phones' => [$phone],
                'message' => $message
            ]);

            if ($response->successful()) {
                return CommunicationLog::create([
                    'user_id' => ($userId && $userId > 0) ? $userId : null,
                    'project_id' => $projectId,
                    'type' => 'sms',
                    'recipient' => $phone,
                    'content' => $message,
                    'status' => 'sent',
                    'provider' => 'webasist'
                ]);
            } else {
                throw new \Exception("Webasist Error: " . $response->body());
            }

        } catch (\Exception $e) {
            Log::error("Real SMS failed to {$phone}: " . $e->getMessage());
            
            return CommunicationLog::create([
                'user_id' => ($userId && $userId > 0) ? $userId : null,
                'project_id' => $projectId,
                'type' => 'sms',
                'recipient' => $phone,
                'content' => $message . "\n\nHATA: " . $e->getMessage(),
                'status' => 'failed',
                'provider' => 'webasist'
            ]);
        }
    }

    /**
     * Gerçek Email Gönderimi (Section 11.4)
     */
    public function sendEmail($userId, $email, $subject, $content, $projectId = null)
    {
        try {
            $user = User::find($userId);
            $userName = $user ? $user->name : null;

            Mail::to($email)->send(new NotificationMail($subject, $content, $userName));

            return CommunicationLog::create([
                'user_id' => ($userId && $userId > 0) ? $userId : null,
                'project_id' => $projectId,
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
                'project_id' => $projectId,
                'type' => 'email',
                'recipient' => $email,
                'content' => "Subject: {$subject}\n\n{$content}\n\nError: " . $e->getMessage(),
                'status' => 'failed',
                'provider' => 'smtp'
            ]);
        }
    }
}
