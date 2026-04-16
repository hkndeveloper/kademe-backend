<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Project;
use App\Models\Activity;
use App\Models\Attendance;
use App\Models\Application;

class AdminController extends Controller
{
    /**
     * Dashboard genel istatistiklerini getirir.
     */
    public function getStats()
    {
        try {
            $totalUsers = User::count();
            $activeProjects = Project::where('is_active', true)->count();
            $upcomingActivities = Activity::where('start_time', '>', now())->count();
            
            // Basit bir ortalama katılım oranı hesaplayalım
            $totalAttendance = Attendance::count();
            $attendedCount = Attendance::where('status', 'present')->count();
            $avgAttendance = $totalAttendance > 0 ? round(($attendedCount / $totalAttendance) * 100) : 0;

            $pendingApplications = Application::where('status', 'pending')->count();
        } catch (\Exception $e) {
            // Tablo henüz oluşturulmamışsa veya DB hatası varsa güvenli varsayılanlar döndür
            \Illuminate\Support\Facades\Log::error('Admin stats hatası: ' . $e->getMessage());
            return response()->json([
                'totalUsers' => $totalUsers ?? 0,
                'activeProjects' => $activeProjects ?? 0,
                'upcomingActivities' => $upcomingActivities ?? 0,
                'avgAttendance' => '0%',
                'pendingApplications' => 0,
            ]);
        }

        return response()->json([
            'totalUsers' => $totalUsers,
            'activeProjects' => $activeProjects,
            'upcomingActivities' => $upcomingActivities,
            'avgAttendance' => $avgAttendance . '%',
            'pendingApplications' => $pendingApplications,
        ]);
    }

    /**
     * Öğrenciyi Mezun Et (Gereksinim 7 ve 14)
     */
    public function makeAlumni(Request $request, $userId)
    {
        $user = User::findOrFail($userId);

        // 1. Rolü 'alumni' olarak güncelle
        $user->syncRoles(['alumni']);

        // 2. Profil statüsünü 'graduated' yap ve CV kodu oluştur
        $profile = $user->participantProfile;
        if ($profile) {
            $profile->update([
                'status' => 'alumni',
                'cv_uuid' => $profile->cv_uuid ?? (string) \Illuminate\Support\Str::uuid(),
                'public_cv' => true
            ]);
        }

        return response()->json([
            'message' => "{$user->name} başarıyla mezun edildi.",
            'cv_link' => $profile ? "/cv/" . $profile->cv_uuid : null
        ]);
    }

    /**
     * Görsel Analitik Dashboard (Section 11.16)
     * Doluluk oranları ve SMS harcamaları grafiksel sunumu
     */
    public function getVisualAnalytics()
    {
        // Project occupancy rates
        $projects = Project::withCount('applications')->get();
        $occupancyData = $projects->map(function ($project) {
            $acceptedCount = Application::where('project_id', $project->id)
                ->where('status', 'accepted')
                ->count();
            $capacity = $project->capacity ?? 100; // Default capacity if not set
            $occupancyRate = $capacity > 0 ? round(($acceptedCount / $capacity) * 100) : 0;

            return [
                'project_name' => $project->name,
                'accepted_count' => $acceptedCount,
                'capacity' => $capacity,
                'occupancy_rate' => $occupancyRate,
                'pending_count' => Application::where('project_id', $project->id)
                    ->where('status', 'pending')
                    ->count()
            ];
        });

        // SMS expenses (mock data - in production, integrate with Webasist API)
        $smsExpenses = [
            'total_sent' => 0,
            'monthly_cost' => 0, 
            'by_project' => $projects->map(function ($project) {
                return [
                    'project_name' => $project->name,
                    'sms_count' => 0
                ];
            })
        ];

        return response()->json([
            'occupancy_rates' => $occupancyData,
            'sms_expenses' => $smsExpenses
        ]);
    }
}
