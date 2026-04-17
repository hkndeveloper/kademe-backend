<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Project;
use App\Models\Activity;
use App\Models\User;

class PublicController extends Controller
{
    /**
     * Ziyaretçi sitesi anasayfası için dinamik istatistikler
     */
    public function getStats()
    {
        $alumniCount = User::role('alumni')->count();
        $activeProjects = Project::where('is_active', true)->count();
        $totalActivities = Activity::count();
        
        // Memnuniyet oranı ileride feedback eklendiğinde dinamiklesebilir, şimdilik sabit
        $satisfactionRate = 96; 

        return response()->json([
            'alumni_count' => $alumniCount,
            'active_projects' => $activeProjects,
            'total_activities' => $totalActivities,
            'satisfaction_rate' => $satisfactionRate
        ]);
    }

    /**
     * Public Proje Sayfası detayları (İçerik, Faaliyetler, Katılımcılar, Materyaller)
     */
    public function getProjectDetails($id)
    {
        $project = Project::with([
            'activities' => function($q) {
                // Sadece herkese açık olan program akışını ve takvimi getir
                $q->orderBy('start_time', 'asc');
            },
            'applications' => function($q) {
                // Sadece kabul edilen veya mezun olan katılımcıları göster
                $q->whereIn('status', ['accepted', 'completed'])
                  ->with(['user.participantProfile', 'user.roles']);
            },
            'materials' => function($q) {
                // Sadece is_public olanları getir (Section 13.1 Boş Materyalleri)
                $q->where('is_public', true);
            }
        ])->findOrFail($id);

        // KVKK Koruması: Hassas verileri maskeliyoruz
        $participants = $project->applications->map(function($app) {
            $user = $app->user;
            $profile = $user->participantProfile;
            if (!$user) return null;

            return [
                'id' => $user->id,
                'name' => $user->name,
                'university' => $profile ? $profile->university : 'Belirtilmemiş',
                'department' => $profile ? $profile->department : 'Belirtilmemiş',
                'is_alumni' => $user->hasRole('alumni')
            ];
        })->filter()->values();

        return response()->json([
            'project' => $project,
            'participants' => $participants,
            'public_materials' => $project->materials
        ]);
    }
}
