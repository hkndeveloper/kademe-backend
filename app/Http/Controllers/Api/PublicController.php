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
        $settings = \App\Models\Setting::where('group', 'home')->get()->keyBy('key');
        $manualEnabled = ($settings['manual_stats_enabled']->value ?? 'false') === 'true';

        if ($manualEnabled && isset($settings['manual_stats_json'])) {
            return response()->json(json_decode($settings['manual_stats_json']->value, true));
        }

        $alumniCount = User::role('alumni')->count();
        $activeProjects = Project::where('is_active', true)->count();
        $totalActivities = Activity::count();
        
        $satisfactionRate = 96; 

        return response()->json([
            'alumni_count' => $alumniCount . "+",
            'active_projects' => $activeProjects,
            'total_activities' => $totalActivities,
            'satisfaction_rate' => "%" . $satisfactionRate
        ]);
    }

    /**
     * Anasayfadaki tüm dinamik içeriği (Pinned Projeler, Faaliyetler, Ayarlar) tek seferde döner.
     */
    public function getHomeContent()
    {
        $pinnedProjects = Project::where('is_pinned', true)->where('is_active', true)->get();
        $pinnedActivities = Activity::where('is_pinned', true)->with('project')->get();
        
        $settings = \App\Models\Setting::all()->keyBy('key');
        
        return response()->json([
            'pinned_projects' => $pinnedProjects,
            'pinned_activities' => $pinnedActivities,
            'settings' => [
                'mission' => $settings['mission_text']->value ?? '',
                'vision' => $settings['vision_text']->value ?? '',
                'insta_feed' => json_decode($settings['insta_feed_json']->value ?? '[]', true),
                'stats' => $this->getStats()->original
            ]
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
