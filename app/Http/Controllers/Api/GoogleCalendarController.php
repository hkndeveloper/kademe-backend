<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Services\GoogleCalendarService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class GoogleCalendarController extends Controller
{
    public function __construct(protected GoogleCalendarService $calendarService)
    {
    }

    public function authUrl(Request $request)
    {
        $state = Str::random(40);
        $client = $this->calendarService->makeClient();
        $client->setState($state);

        Cache::put("google_calendar_oauth_state:{$state}", [
            'user_id' => $request->user()->id,
        ], now()->addMinutes(10));

        return response()->json([
            'auth_url' => $client->createAuthUrl(),
        ]);
    }

    public function callback(Request $request)
    {
        $state = (string) $request->query('state');
        $code = (string) $request->query('code');

        $cachedState = Cache::pull("google_calendar_oauth_state:{$state}");

        if (! $cachedState || $code === '') {
            return redirect($this->frontendRedirectUrl('error'));
        }

        try {
            $client = $this->calendarService->makeClient();
            $token = $client->fetchAccessTokenWithAuthCode($code);

            if (isset($token['error'])) {
                return redirect($this->frontendRedirectUrl('error'));
            }

            $existingToken = $this->getStoredToken();

            if (empty($token['refresh_token']) && ! empty($existingToken['refresh_token'])) {
                $token['refresh_token'] = $existingToken['refresh_token'];
            }

            Setting::updateOrCreate(
                ['key' => 'google_calendar_tokens'],
                [
                    'value' => json_encode($token, JSON_UNESCAPED_SLASHES),
                    'group' => 'integrations',
                    'type' => 'json',
                ]
            );

            return redirect($this->frontendRedirectUrl('success'));
        } catch (\Exception $e) {
            return redirect($this->frontendRedirectUrl('error'));
        }
    }

    public function status()
    {
        $token = $this->getStoredToken();

        return response()->json([
            'connected' => ! empty($token['access_token']) || ! empty($token['refresh_token']),
            'last_synced_at' => Setting::where('key', 'google_calendar_last_synced_at')->value('value'),
            'calendar_id' => config('services.google_calendar.calendar_id', 'primary'),
        ]);
    }

    public function syncAll()
    {
        try {
            $syncedCount = $this->calendarService->syncAll();

            return response()->json([
                'message' => 'Google Takvim senkronizasyonu tamamlandi.',
                'synced_count' => $syncedCount,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Hata: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function syncFromGoogle()
    {
        try {
            $result = $this->calendarService->syncFromGoogle();

            return response()->json([
                'message' => 'Google Takvim verileri basariyla alindi.',
                'updated_count' => $result['updated_count'],
                'errors' => $result['errors']
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Hata: ' . $e->getMessage(),
            ], 500);
        }
    }

    protected function getStoredToken(): array
    {
        $rawToken = Setting::where('key', 'google_calendar_tokens')->value('value');

        if (! $rawToken) {
            return [];
        }

        return json_decode($rawToken, true) ?: [];
    }

    protected function frontendRedirectUrl(string $status): string
    {
        $baseUrl = rtrim(config('services.google_calendar.frontend_redirect'), '/');

        return "{$baseUrl}?google_calendar={$status}";
    }
}
