<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Activity;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ActivityController extends Controller
{
    public function index(Request $request)
    {
        $query = Activity::query();
        
        if ($request->filled('project_id')) {
            $query->where('project_id', $request->project_id);
        }

        $activities = $query->with('project')->get();

        $user = auth('sanctum')->user();
        
        // Eğer kullanıcı öğrenci ise ve admin değilse veri maskelemesi (Censorship) yap
        if ($user && !$user->hasRole('admin')) {
            $acceptedProjectIds = \App\Models\Application::where('user_id', $user->id)
                ->where('status', 'accepted')
                ->pluck('project_id')
                ->toArray();
                
            $activities->transform(function ($activity) use ($acceptedProjectIds) {
                // Öğrenci bu faaliyetin projesine kabul EDiLMEDİYSE
                if (!in_array($activity->project_id, $acceptedProjectIds)) {
                    $activity->description = 'Bu faaliyetin detaylarını görüntülemek için ilgili programa kayıtlı olmalısınız.';
                    $activity->room_name = 'Gizli Konum';
                    $activity->latitude = null;
                    $activity->longitude = null;
                    $activity->qr_code_secret = null;
                    $activity->is_accessible = false; // Frontend'in butonu kapatması için bayrak
                } else {
                    $activity->is_accessible = true;
                }
                return $activity;
            });
        }

        return response()->json($activities);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'project_id' => 'required|exists:projects,id',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'type' => 'required|in:program,training,event',
            'start_time' => 'required|date',
            'end_time' => 'required|date|after:start_time',
            'room_name' => 'nullable|string|max:100',
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
            'radius' => 'nullable|integer',
            'credit_loss_amount' => 'nullable|integer',
        ]);

        // QR Secret üretimi
        $validated['qr_code_secret'] = Str::random(32);

        $activity = Activity::create($validated);

        return response()->json($activity, 201);
    }

    public function show(Activity $activity)
    {
        return response()->json($activity->load(['project', 'attendances']));
    }

    public function update(Request $request, Activity $activity)
    {
        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string',
            'type' => 'sometimes|required|in:program,training,event',
            'start_time' => 'sometimes|required|date',
            'end_time' => 'sometimes|required|date|after:start_time',
            'room_name' => 'nullable|string|max:100',
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
            'radius' => 'nullable|integer',
            'credit_loss_amount' => 'nullable|integer',
            'is_active' => 'boolean',
        ]);

        $activity->update($validated);

        return response()->json($activity);
    }

    public function destroy(Activity $activity)
    {
        $activity->delete();
        return response()->json(['message' => 'Faaliyet silindi.']);
    }
    
    /**
     * Yeni bir QR kod üretme (Admin tarafından tetiklenir)
     */
    public function refreshQRCode(Activity $activity)
    {
        $activity->update([
            'qr_code_secret' => Str::random(32)
        ]);

        // Create QR payload with activity info
        $payload = json_encode([
            'activity_id' => $activity->id,
            'secret' => $activity->qr_code_secret,
            'timestamp' => now()->timestamp
        ]);

        return response()->json([
            'message' => 'QR kod yenilendi.',
            'payload' => $payload,
            'activity' => [
                'id' => $activity->id,
                'name' => $activity->name,
                'latitude' => $activity->latitude,
                'longitude' => $activity->longitude,
                'radius' => $activity->radius,
                'start_time' => $activity->start_time,
                'end_time' => $activity->end_time
            ]
        ]);
    }

    /**
     * Google Takvim (ICS) Dışa Aktarımı
     */
    public function exportIcs(Activity $activity)
    {
        $start = \Carbon\Carbon::parse($activity->start_time)->utc()->format('Ymd\THis\Z');
        $end = \Carbon\Carbon::parse($activity->end_time)->utc()->format('Ymd\THis\Z');
        $now = \Carbon\Carbon::now()->utc()->format('Ymd\THis\Z');
        $uid = $activity->id . '@kademe.org';

        $ics = "BEGIN:VCALENDAR\r\n" .
               "VERSION:2.0\r\n" .
               "PRODID:-//KADEME//NONSGML v1.0//EN\r\n" .
               "BEGIN:VEVENT\r\n" .
               "UID:{$uid}\r\n" .
               "DTSTAMP:{$now}\r\n" .
               "DTSTART:{$start}\r\n" .
               "DTEND:{$end}\r\n" .
               "SUMMARY:{$activity->name}\r\n" .
               "DESCRIPTION:{$activity->description}\r\n";
               
        if ($activity->latitude && $activity->longitude) {
            $ics .= "LOCATION:{$activity->latitude}, {$activity->longitude}\r\n";
        }
        
        $ics .= "END:VEVENT\r\n" .
                "END:VCALENDAR\r\n";

        return response($ics)
            ->header('Content-Type', 'text/calendar; charset=utf-8')
            ->header('Content-Disposition', 'attachment; filename="faaliyet_' . $activity->id . '.ics"');
    }

    /**
     * Dinamik QR kod görselini döndürür.
     * Admin panelinde ekran yansıtma için kullanılır.
     */
    public function getDynamicQr(Activity $activity)
    {
        $dynamicSecret = $activity->getDynamicQrSecret();
        
        // QR içeriği: Sadece secret (Örn: hash)
        // Öğrenci uygulaması bu kodu okuyup /api/attendance/store'a gönderecek
        $qrUrl = "https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=" . urlencode($dynamicSecret);
        
        return response()->json([
            'activity_id' => $activity->id,
            'activity_name' => $activity->name,
            'qr_url' => $qrUrl,
            'expires_in' => 30 - (time() % 30), // Yeni koda kalan saniye
            'secret' => $dynamicSecret // Frontend kontrolü için
        ]);
    }
}
