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
        $user = auth()->user();
        $isCoordinator = $user->hasRole('coordinator') && !$user->hasRole('super-admin');
        
        try {
            if ($isCoordinator) {
                $projectIds = $user->coordinatedProjects->pluck('id');
                $totalUsers = User::whereHas('applications', function($q) use ($projectIds) {
                    $q->whereIn('project_id', $projectIds);
                })->count();
                $activeProjects = Project::whereIn('id', $projectIds)->where('is_active', true)->count();
                $upcomingActivities = Activity::whereIn('project_id', $projectIds)->where('start_time', '>', now())->count();
                
                $totalAttendance = Attendance::whereHas('activity', function($q) use ($projectIds) {
                    $q->whereIn('project_id', $projectIds);
                })->count();
                $attendedCount = Attendance::whereHas('activity', function($q) use ($projectIds) {
                    $q->whereIn('project_id', $projectIds);
                })->where('status', 'attended')->count();
                
                $pendingApplications = Application::whereIn('project_id', $projectIds)->where('status', 'pending')->count();
                $totalMaterials = \App\Models\ProjectMaterial::whereIn('project_id', $projectIds)->count();
            } else {
                $totalUsers = User::count();
                $activeProjects = Project::where('is_active', true)->count();
                $upcomingActivities = Activity::where('start_time', '>', now())->count();
                $totalAttendance = Attendance::count();
                $attendedCount = Attendance::where('status', 'attended')->count();
                $pendingApplications = Application::where('status', 'pending')->count();
                $totalMaterials = \App\Models\ProjectMaterial::count();
            }

            $avgAttendance = $totalAttendance > 0 ? round(($attendedCount / $totalAttendance) * 100) : 0;
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Admin stats hatası: ' . $e->getMessage());
            return response()->json([
                'totalUsers' => 0,
                'activeProjects' => 0,
                'upcomingActivities' => 0,
                'avgAttendance' => '0%',
                'pendingApplications' => 0,
                'totalMaterials' => 0,
            ]);
        }

        return response()->json([
            'totalUsers' => $totalUsers,
            'activeProjects' => $activeProjects,
            'upcomingActivities' => $upcomingActivities,
            'avgAttendance' => $avgAttendance . '%',
            'pendingApplications' => $pendingApplications,
            'totalMaterials' => $totalMaterials
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
        $user = auth()->user();
        $isCoordinator = $user->hasRole('coordinator') && !$user->hasRole('super-admin');

        $query = Project::withCount('applications');
        if ($isCoordinator) {
            $projectIds = $user->coordinatedProjects->pluck('id');
            $query->whereIn('id', $projectIds);
        }
        $projects = $query->get();

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

        // SMS expenses (real data from CommunicationLogs)
        $smsQuery = \App\Models\CommunicationLog::where('type', 'sms');
        if ($isCoordinator) {
            $smsQuery->whereIn('project_id', $user->coordinatedProjects->pluck('id'));
        }

        $totalSms = $smsQuery->count();
        $monthlySms = (clone $smsQuery)->where('created_at', '>=', now()->startOfMonth())->count();
        $costPerSms = 0.15; // TRY (Şartname 11.19 / Webasist birim maliyet)

        $smsExpenses = [
            'total_sent' => $totalSms,
            'monthly_cost' => $monthlySms * $costPerSms,
            'by_project' => $projects->map(function ($project) {
                return [
                    'project_name' => $project->name,
                    'sms_count' => \App\Models\CommunicationLog::where('project_id', $project->id)->where('type', 'sms')->count()
                ];
            })
        ];

        return response()->json([
            'occupancy_rates' => $occupancyData,
            'sms_expenses' => $smsExpenses
        ]);
    }

    public function getCoordinators()
    {
        $coordinators = User::role('coordinator')->get(['id', 'name', 'email']);
        return response()->json($coordinators);
    }
}
