<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\KpdAppointment;
use Carbon\Carbon;

class KpdAppointmentController extends Controller
{
    /**
     * Tüm randevuları listele (Admin/Koordinatör)
     */
    public function index()
    {
        $appointments = KpdAppointment::with(['user:id,name,email', 'coordinator:id,name'])
            ->latest()
            ->get();
            
        return response()->json($appointments);
    }

    /**
     * Giriş yapan öğrencinin randevularını getir
     */
    public function myAppointments()
    {
        $appointments = KpdAppointment::where('user_id', auth()->id())
            ->with('coordinator:id,name')
            ->latest()
            ->get();
            
        return response()->json($appointments);
    }

    /**
     * Randevu Al
     */
    public function store(Request $request)
    {
        $request->validate([
            'date' => 'required|date_format:Y-m-d',
            'time' => 'required|date_format:H:i',
            'type' => 'required|in:online,office',
            'room_id' => 'required|in:1,2',
            'topic' => 'nullable|string'
        ]);

        $startTime = Carbon::createFromFormat('Y-m-d H:i', $request->date . ' ' . $request->time);
        $endTime = (clone $startTime)->addMinutes(45);

        // Çakışma Kontrolü (Aynı oda, aynı saat dilimi)
        $exists = KpdAppointment::where('room_id', $request->room_id)
            ->where('status', '!=', 'cancelled')
            ->where(function($q) use ($startTime, $endTime) {
                $q->whereBetween('start_time', [$startTime, $endTime])
                  ->orWhereBetween('end_time', [$startTime, $endTime]);
            })->exists();

        if ($exists) {
            return response()->json(['message' => 'Bu saat dilimi seçilen oda için dolu.'], 422);
        }

        $appointment = KpdAppointment::create([
            'user_id' => auth()->id(),
            'room_id' => $request->room_id,
            'start_time' => $startTime,
            'end_time' => $endTime,
            'type' => $request->type,
            'topic' => $request->topic,
            'status' => 'pending'
        ]);

        return response()->json([
            'message' => 'Randevu talebiniz alındı, onay bekliyor.',
            'appointment' => $appointment
        ], 201);
    }

    /**
     * Randevu durumunu güncelle (Admin/Koordinatör)
     */
    public function update(Request $request, $id)
    {
        $appointment = KpdAppointment::findOrFail($id);
        
        $request->validate([
            'status' => 'required|in:confirmed,completed,cancelled',
            'coordinator_id' => 'nullable|exists:users,id',
            'notes' => 'nullable|string'
        ]);

        $appointment->update($request->only(['status', 'coordinator_id', 'notes']));

        return response()->json(['message' => 'Randevu güncellendi.']);
    }

    /**
     * Belirli bir tarih için müsaitlik kontrolü
     */
    public function checkAvailability(Request $request)
    {
        $date = $request->query('date', Carbon::today()->toDateString());
        
        $occupied = KpdAppointment::whereDate('start_time', $date)
            ->where('status', '!=', 'cancelled')
            ->get(['start_time', 'room_id'])
            ->map(function($app) {
                return [
                    'time' => Carbon::parse($app->start_time)->format('H:i'),
                    'room' => $app->room_id
                ];
            });

        return response()->json($occupied);
    }
}
