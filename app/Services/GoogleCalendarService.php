<?php

namespace App\Services;

use App\Models\Activity;
use App\Models\Setting;
use Google\Client;
use Google\Service\Calendar;
use Google\Service\Calendar\Event;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Support\Facades\Log;

class GoogleCalendarService
{
    public function syncActivity(Activity $activity): bool
    {
        try {
            $service = $this->calendarService();
            $event = $this->buildEventFromActivity($activity);
            $calendarId = config('services.google_calendar.calendar_id', 'primary');

            if ($activity->google_calendar_event_id) {
                try {
                    $service->events->update($calendarId, $activity->google_calendar_event_id, $event);
                } catch (\Google\Service\Exception $e) {
                    if ($e->getCode() == 404) {
                        // Event deleted in Google, recreate it
                        $createdEvent = $service->events->insert($calendarId, $event);
                        $activity->google_calendar_event_id = $createdEvent->id;
                    } else {
                        throw $e;
                    }
                }
            } else {
                $createdEvent = $service->events->insert($calendarId, $event);
                $activity->google_calendar_event_id = $createdEvent->id;
            }

            $activity->google_calendar_last_synced_at = now();
            $activity->saveQuietly(); // Don't trigger observer again

            return true;
        } catch (\Exception $e) {
            Log::error("Google Calendar Sync Error: " . $e->getMessage());
            return false;
        }
    }

    public function syncAll(): int
    {
        $activities = Activity::all();
        $count = 0;
        foreach ($activities as $activity) {
            if ($this->syncActivity($activity)) {
                $count++;
            }
        }

        Setting::updateOrCreate(
            ['key' => 'google_calendar_last_synced_at'],
            [
                'value' => now()->toIso8601String(),
                'group' => 'integrations',
                'type' => 'datetime',
            ]
        );

        return $count;
    }

    public function syncFromGoogle(): array
    {
        $service = $this->calendarService();
        $calendarId = config('services.google_calendar.calendar_id', 'primary');
        
        $activities = Activity::whereNotNull('google_calendar_event_id')->get();
        $updatedCount = 0;
        $errors = [];

        foreach ($activities as $activity) {
            try {
                $googleEvent = $service->events->get($calendarId, $activity->google_calendar_event_id);
                
                // Compare and update if changed in Google
                $changed = false;
                
                if ($googleEvent->getSummary() !== $activity->name) {
                    $activity->name = $googleEvent->getSummary();
                    $changed = true;
                }
                
                if ($googleEvent->getDescription() !== $activity->description) {
                    $activity->description = $googleEvent->getDescription();
                    $changed = true;
                }
                
                $googleStart = \Carbon\Carbon::parse($googleEvent->getStart()->getDateTime())->setTimezone(config('app.timezone'));
                if (!$googleStart->equalTo($activity->start_time)) {
                    $activity->start_time = $googleStart;
                    $changed = true;
                }
                
                $googleEnd = \Carbon\Carbon::parse($googleEvent->getEnd()->getDateTime())->setTimezone(config('app.timezone'));
                if (!$googleEnd->equalTo($activity->end_time)) {
                    $activity->end_time = $googleEnd;
                    $changed = true;
                }

                if ($changed) {
                    $activity->saveQuietly();
                    $updatedCount++;
                }
            } catch (\Google\Service\Exception $e) {
                if ($e->getCode() == 404) {
                    // Event deleted in Google, we might want to mark it or delete it locally?
                    // For now, let's just clear the ID so it can be re-synced if needed
                    $activity->google_calendar_event_id = null;
                    $activity->saveQuietly();
                } else {
                    $errors[] = "Activity {$activity->id}: " . $e->getMessage();
                }
            }
        }

        return [
            'updated_count' => $updatedCount,
            'errors' => $errors
        ];
    }

    public function deleteActivity(Activity $activity): bool
    {
        if (!$activity->google_calendar_event_id) {
            return true;
        }

        try {
            $service = $this->calendarService();
            $calendarId = config('services.google_calendar.calendar_id', 'primary');
            $service->events->delete($calendarId, $activity->google_calendar_event_id);
            return true;
        } catch (\Google\Service\Exception $e) {
            if ($e->getCode() == 404) return true;
            Log::error("Google Calendar Delete Error: " . $e->getMessage());
            return false;
        } catch (\Exception $e) {
            Log::error("Google Calendar Delete Error: " . $e->getMessage());
            return false;
        }
    }

    protected function buildEventFromActivity(Activity $activity): Event
    {
        $location = null;
        if ($activity->latitude && $activity->longitude) {
            $location = "{$activity->latitude}, {$activity->longitude}";
        }

        return new Event([
            'summary' => $activity->name,
            'description' => $activity->description ?: 'KADEME faaliyet kaydi',
            'location' => $location,
            'start' => [
                'dateTime' => $activity->start_time?->toIso8601String(),
                'timeZone' => config('app.timezone'),
            ],
            'end' => [
                'dateTime' => $activity->end_time?->toIso8601String(),
                'timeZone' => config('app.timezone'),
            ],
            'extendedProperties' => [
                'private' => [
                    'kademe_activity_id' => (string) $activity->id,
                    'kademe_project' => (string) optional($activity->project)->name,
                ],
            ],
        ]);
    }

    public function calendarService(): Calendar
    {
        $client = $this->makeClient();
        $token = $this->getStoredToken();

        if (empty($token)) {
            throw new \Exception('Google Takvim baglantisi bulunamadi.');
        }

        $client->setAccessToken($token);

        if ($client->isAccessTokenExpired()) {
            if (empty($token['refresh_token'])) {
                throw new \Exception('Google Takvim baglantisi yeniden yetkilendirilmeli.');
            }

            $newToken = $client->fetchAccessTokenWithRefreshToken($token['refresh_token']);

            if (isset($newToken['error'])) {
                throw new \Exception('Google token yenilenemedi: ' . ($newToken['error_description'] ?? $newToken['error']));
            }

            $newToken['refresh_token'] = $newToken['refresh_token'] ?? $token['refresh_token'];

            Setting::updateOrCreate(
                ['key' => 'google_calendar_tokens'],
                [
                    'value' => json_encode($newToken, JSON_UNESCAPED_SLASHES),
                    'group' => 'integrations',
                    'type' => 'json',
                ]
            );

            $client->setAccessToken($newToken);
        }

        return new Calendar($client);
    }

    public function makeClient(): Client
    {
        if (!class_exists(Client::class)) {
             throw new \Exception('Google Calendar istemcisi sunucuda hazir degil.');
        }

        $client = new Client();
        $client->setClientId(config('services.google_calendar.client_id'));
        $client->setClientSecret(config('services.google_calendar.client_secret'));
        $client->setRedirectUri(config('services.google_calendar.redirect'));
        $client->setAccessType('offline');
        $client->setPrompt('consent');
        $client->setIncludeGrantedScopes(true);
        $client->setScopes([
            Calendar::CALENDAR,
        ]);

        return $client;
    }

    protected function getStoredToken(): array
    {
        $rawToken = Setting::where('key', 'google_calendar_tokens')->value('value');
        if (!$rawToken) return [];
        return json_decode($rawToken, true) ?: [];
    }
}
