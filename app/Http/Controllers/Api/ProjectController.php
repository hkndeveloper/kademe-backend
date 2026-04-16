<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Project;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ProjectController extends Controller
{
    public function index()
    {
        return response()->json(Project::all());
    }

    public function getPublicStats()
    {
        $alumniCount = \App\Models\ParticipantProfile::where('status', 'alumni')->count() ?? 0;
        $projectsCount = \App\Models\Project::where('is_active', true)->count() ?? 0;
        $activitiesCount = \App\Models\Activity::count() ?? 0;
        
        // Memnuniyet oranı dinamik (Feedback'lerden ortalama alınır, yoksa %100 default başlar)
        $satisfaction = 100;
        try {
            if (\Schema::hasTable('activity_feedback')) {
                $avgRow = \DB::table('activity_feedback')->avg('rating');
                if ($avgRow) {
                    $satisfaction = min(100, round(($avgRow / 5) * 100));
                }
            }
        } catch (\Exception $e) {}

        return response()->json([
            'alumni_count' => $alumniCount,
            'active_projects' => $projectsCount,
            'total_activities' => $activitiesCount,
            'satisfaction_rate' => $satisfaction
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'logo' => 'nullable|string',
        ]);

        $validated['slug'] = Str::slug($validated['name']);

        $project = Project::create($validated);

        return response()->json($project, 201);
    }

    public function show($idOrSlug)
    {
        $project = Project::where(is_numeric($idOrSlug) ? 'id' : 'slug', $idOrSlug)
            ->with(['activities', 'applications.user.participantProfile'])
            ->firstOrFail();

        // İstatistikleri hesapla
        $acceptedApplications = $project->applications->where('status', 'accepted');
        $participantsCount = $acceptedApplications->count();
        
        // Üniversite özeti (En popüler olanı bul)
        $topInfo = "Aktif Katılımcı Yok";
        if ($participantsCount > 0) {
            $unis = $acceptedApplications->map(function($app) {
                return $app->user->participantProfile->university ?? 'Bilinmiyor';
            })->countBy()->sortDesc();
            
            $topUni = $unis->keys()->first();
            $topInfo = "{$topUni} • En Çok Katılım";
        }

        // Project objesine ekle (toarray'e dahil olması için appends veya manuel)
        $projectData = $project->toArray();
        $projectData['stats'] = [
            'participants_count' => $participantsCount,
            'top_info' => $topInfo
        ];

        return response()->json($projectData);
    }

    public function update(Request $request, Project $project)
    {
        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string',
            'logo' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        if (isset($validated['name'])) {
            $validated['slug'] = Str::slug($validated['name']);
        }

        $project->update($validated);

        return response()->json($project);
    }

    public function bulkAttendance(Project $project)
    {
        $activities = $project->activities;
        $acceptedUserIds = $project->applications()->where('status', 'accepted')->pluck('user_id');

        foreach ($activities as $activity) {
            foreach ($acceptedUserIds as $userId) {
                \App\Models\Attendance::firstOrCreate([
                    'activity_id' => $activity->id,
                    'user_id' => $userId,
                ], [
                    'status' => 'present',
                    'marked_at' => now(),
                    'method' => 'manual_bulk'
                ]);
            }
        }

        return response()->json(['message' => 'Tüm katılımcılar için toplu yoklama başarıyla işlendi.']);
    }

    public function destroy(Project $project)
    {
        $project->delete();
        return response()->json(['message' => 'Proje silindi.']);
    }
}
