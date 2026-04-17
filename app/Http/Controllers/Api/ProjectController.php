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
        $user = auth('sanctum')->user();
        $query = Project::withCount(['applications' => function($q) {
            $q->where('status', 'accepted');
        }]);

        if ($user && ($user->hasRole('coordinator') || $user->hasRole('staff')) && !$user->hasRole('super-admin')) {
            $query->whereHas('coordinators', function($q) use ($user) {
                $q->where('user_id', $user->id);
            });
        }

        return response()->json($query->get());
    }

    public function getPublicStats()
    {
        $alumniCount = \App\Models\ParticipantProfile::where('status', 'alumni')->count() ?? 0;
        $projectsCount = \App\Models\Project::where('is_active', true)->count() ?? 0;
        $activitiesCount = \App\Models\Activity::count() ?? 0;
        
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
            'description' => 'required|string',
            'sub_description' => 'nullable|string',
            'location' => 'nullable|string',
            'capacity' => 'nullable|integer',
            'format' => 'nullable|string',
            'period' => 'nullable|string',
            'application_deadline' => 'nullable|date',
            'is_active' => 'boolean',
            'timeline' => 'nullable', 
            'coordinator_ids' => 'nullable|array',
            'coordinator_ids.*' => 'exists:users,id',
        ]);

        $validated['slug'] = Str::slug($validated['name']);

        if (is_string($request->timeline)) {
            $validated['timeline'] = json_decode($request->timeline, true);
        }

        // Handle File Uploads - 11.4 / 9.0 Belgeler
        $documentList = [];
        if ($request->hasFile('document_files')) {
            foreach ($request->file('document_files') as $index => $file) {
                $title = $request->document_titles[$index] ?? $file->getClientOriginalName();
                $path = $file->store('projects/documents', 'public');
                $documentList[] = [
                    'title' => $title,
                    'url' => asset('storage/' . $path)
                ];
            }
        }
        $validated['documents'] = $documentList;

        $project = Project::create($validated);

        if ($request->has('coordinator_ids')) {
            $project->coordinators()->sync($request->coordinator_ids);
        }

        return response()->json($project->load('coordinators'), 201);
    }

    public function show($idOrSlug)
    {
        $project = Project::where(is_numeric($idOrSlug) ? 'id' : 'slug', $idOrSlug)
            ->with(['activities', 'applications' => function($q) {
                $q->orderBy('status', 'asc')->orderBy('waitlist_order', 'asc')->orderBy('created_at', 'asc');
            }, 'applications.user.participantProfile', 'coordinators'])
            ->firstOrFail();

        $user = auth('sanctum')->user();
        if ($user && ($user->hasRole('coordinator') || $user->hasRole('staff')) && !$user->hasRole('super-admin')) {
            if (!$project->coordinators->contains($user->id)) {
                return response()->json(['message' => 'Bu projeye erişim yetkiniz yok.'], 403);
            }
        }

        $acceptedApplications = $project->applications->where('status', 'accepted');
        $participantsCount = $acceptedApplications->count();
        
        $topInfo = "Aktif Katılımcı Yok";
        if ($participantsCount > 0) {
            $unis = $acceptedApplications->map(function($app) {
                return $app->user->participantProfile->university ?? 'Bilinmiyor';
            })->countBy()->sortDesc();
            
            $topUni = $unis->keys()->first();
            $topInfo = "{$topUni} • En Çok Katılım";
        }

        $projectData = $project->toArray();
        $projectData['stats'] = [
            'participants_count' => $participantsCount,
            'top_info' => $topInfo
        ];

        return response()->json($projectData);
    }

    public function update(Request $request, Project $project)
    {
        try {
            $user = auth()->user();
            if ($user) {
                $this->authorize('manageProject', $project);
            }

            $validated = $request->validate([
                'name' => 'sometimes|required|string|max:255',
                'description' => 'sometimes|required|string',
                'sub_description' => 'nullable|string',
                'location' => 'nullable|string',
                'capacity' => 'nullable|integer',
                'format' => 'nullable|string',
                'period' => 'nullable|string',
                'application_deadline' => 'nullable|date',
                'is_active' => 'boolean',
                'timeline' => 'nullable', 
                'coordinator_ids' => 'nullable|array',
                'coordinator_ids.*' => 'exists:users,id',
            ]);

            if (isset($validated['name'])) {
                $validated['slug'] = Str::slug($validated['name']);
            }

            if ($request->has('timeline') && is_string($request->timeline)) {
                $validated['timeline'] = json_decode($request->timeline, true);
            }

            // Handle File Uploads - 11.4 / 9.0 Belgeler
            $documentList = $project->documents ?? [];
            if ($request->hasFile('document_files')) {
                foreach ($request->file('document_files') as $index => $file) {
                    $title = $request->document_titles[$index] ?? $file->getClientOriginalName();
                    $path = $file->store('projects/documents', 'public');
                    $documentList[] = [
                        'title' => $title,
                        'url' => asset('storage/' . $path)
                    ];
                }
            }
            $validated['documents'] = $documentList;

            $project->update($validated);

            if ($request->has('coordinator_ids')) {
                $project->coordinators()->sync($request->coordinator_ids);
            }

            return response()->json($project->load('coordinators'));
        } catch (\Throwable $e) {
            return response()->json([
                'message' => 'DEBUG HATASI: ' . $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ], 500);
        }
    }

    public function bulkAttendance(Project $project)
    {
        $user = auth()->user();
        if ($user) {
            $this->authorize('takeAttendance', $project);
        }

        $activities = $project->activities;
        $acceptedUserIds = $project->applications()->where('status', 'accepted')->pluck('user_id');

        foreach ($activities as $activity) {
            foreach ($acceptedUserIds as $userId) {
                \App\Models\Attendance::firstOrCreate([
                    'activity_id' => $activity->id,
                    'user_id' => $userId,
                ], [
                    'status' => 'attended',
                ]);
            }
        }

        return response()->json(['message' => 'Tüm katılımcılar için toplu yoklama başarıyla işlendi.']);
    }

    public function destroy(Project $project)
    {
        if (!auth()->user()->hasRole('super-admin')) {
            return response()->json(['message' => 'Sadece Üst Admin proje silebilir.'], 403);
        }

        $project->delete();
        return response()->json(['message' => 'Proje silindi.']);
    }
}
