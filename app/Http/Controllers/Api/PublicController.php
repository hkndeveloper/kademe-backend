<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Project;
use App\Models\Activity;
use App\Models\User;
use App\Models\Slider;
use App\Models\Faq;
use App\Models\InstagramPost;
use App\Models\Setting;

class PublicController extends Controller
{
    /**
     * Ziyaretçi sitesi anasayfası için istatistikler
     */
    public function getStats()
    {
        $settings = Setting::where('group', 'home')->get()->keyBy('key');
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
     * Anasayfadaki tüm dinamik içeriği tek seferde döner.
     */
    public function getHomeContent()
    {
        // 1. Sliders (Pinned or Active)
        $sliders = Slider::where('is_active', true)->orderBy('order_priority')->get();

        // 2. Pinned Content
        $pinnedProjects = Project::where('is_pinned', true)->where('is_active', true)->get();
        $pinnedActivities = Activity::where('is_pinned', true)->with('project')->get();
        
        // 3. Instagram (Manual)
        $instagramPosts = InstagramPost::where('is_active', true)->orderBy('order_priority', 'desc')->take(6)->get();

        // 4. FAQ Snippet (Top 5)
        $faqs = Faq::where('is_active', true)->orderBy('order_priority')->take(5)->get();

        // 5. Blog Snippet (Pinned/Latest)
        $posts = \App\Models\Post::where('status', 'published')->latest()->take(4)->get();

        $settings = Setting::all()->keyBy('key');
        
        return response()->json([
            'sliders' => $sliders,
            'pinned_projects' => $pinnedProjects,
            'pinned_activities' => $pinnedActivities,
            'instagram_posts' => $instagramPosts,
            'faqs' => $faqs,
            'latest_posts' => $posts,
            'settings' => [
                'mission' => $settings['mission_text']->value ?? '',
                'vision' => $settings['vision_text']->value ?? '',
                'stats' => $this->getStats()->original
            ]
        ]);
    }

    /**
     * PUBLIC Project Details (Enriched with Students & Badges)
     */
    public function getProjectDetails($id)
    {
        $project = Project::with([
            'activities' => fn($q) => $q->orderBy('start_time', 'asc'),
            'applications' => function($q) {
                $q->whereIn('status', ['accepted', 'completed'])
                  ->with(['user.participantProfile', 'user.badges']);
            },
            'materials' => fn($q) => $q->where('is_public', true)
        ])->findOrFail($id);

        $participants = $project->applications->map(function($app) {
            $user = $app->user;
            $profile = $user?->participantProfile;
            if (!$user) return null;

            return [
                'id' => $user->id,
                'name' => $user->name,
                'university' => $profile ? $profile->university : 'Belirtilmemiş',
                'department' => $profile ? $profile->department : 'Belirtilmemiş',
                'is_alumni' => $user->hasRole('alumni'),
                'badges' => $user->badges->take(3) // Top 3 badges for public view
            ];
        })->filter()->values();

        return response()->json([
            'project' => $project,
            'participants' => $participants,
            'public_materials' => $project->materials
        ]);
    }

    /**
     * Tüm Sıkça Sorulan Sorular
     */
    public function getFaqs()
    {
        return response()->json(Faq::where('is_active', true)->orderBy('order_priority')->get());
    }
}
