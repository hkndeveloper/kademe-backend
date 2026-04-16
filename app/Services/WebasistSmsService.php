<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WebasistSmsService
{
    protected $apiUrl;
    protected $apiKey;
    protected $apiSecret;
    protected $senderName;

    public function __construct()
    {
        // Settings are pulled from .env. The user must provide these credentials.
        $this->apiUrl = env('WEBASIST_SMS_URL', 'https://api.webasist.com.tr/sms/send');
        $this->apiKey = env('WEBASIST_API_KEY', '');
        $this->apiSecret = env('WEBASIST_API_SECRET', '');
        $this->senderName = env('WEBASIST_SENDER_NAME', 'KADEME');
    }

    /**
     * Send SMS to a single number or array of numbers.
     * 
     * @param string|array $to Phone number(s)
     * @param string $message The SMS content
     * @return bool
     */
    public function sendSms($to, $message)
    {
        if (empty($this->apiKey) || empty($this->apiSecret)) {
            Log::warning("Webasist SMS API credentials not set. Simulating SMS to: " . json_encode($to) . " | Message: $message");
            return true; // Simulate success for local testing without real credentials
        }

        $phones = is_array($to) ? $to : [$to];

        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->apiKey, // Example auth mechanism
                'Content-Type' => 'application/json'
            ])->post($this->apiUrl, [
                'secret' => $this->apiSecret,
                'sender' => $this->senderName,
                'phones' => $phones,
                'message' => $message
            ]);

            if ($response->successful()) {
                Log::info("SMS successfully sent via Webasist to: " . json_encode($phones));
                return true;
            } else {
                Log::error("Webasist SMS failed. Response: " . $response->body());
                return false;
            }
        } catch (\Exception $e) {
            Log::error("Webasist SMS Exception: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Toplu listeye ve filtrelenmiş kullanıcılara SMS atma.
     * @param array $participantIds
     * @param string $message
     */
    public function sendBulkSms($participantIds, $message)
    {
        $phones = \App\Models\ParticipantProfile::whereIn('user_id', $participantIds)
            ->pluck('phone')
            ->toArray();

        // Phone format validation can be added here
        return $this->sendSms($phones, $message);
    }
}
