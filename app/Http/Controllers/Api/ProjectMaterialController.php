<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\ProjectMaterial;
use Illuminate\Support\Facades\Storage;

class ProjectMaterialController extends Controller
{
    /**
     * Projeye ait tüm materyalleri listele
     */
    public function index($projectId)
    {
        $user = auth()->user();
        if ($user && $user->hasRole('coordinator') && !$user->hasRole('super-admin')) {
            if (!$user->coordinatedProjects->contains($projectId)) {
                return response()->json(['message' => 'Bu projenin materyallerini görme yetkiniz yok.'], 403);
            }
        }

        $materials = ProjectMaterial::where('project_id', $projectId)->get();
        return response()->json($materials);
    }

    /**
     * Sadece yetkili koordinatörlerin materyal yüklemesi (Section 9)
     */
    public function store(Request $request, $projectId)
    {
        $user = auth()->user();
        if ($user && $user->hasRole('coordinator') && !$user->hasRole('super-admin')) {
            if (!$user->coordinatedProjects->contains($projectId)) {
                return response()->json(['message' => 'Bu projeye materyal yükleme yetkiniz yok.'], 403);
            }
        }

        $request->validate([
            'title' => 'required|string|max:255',
            'type' => 'required|in:document,video,image',
            'content' => 'required_without:file|string',
            'file' => 'nullable|file|max:10240', // 10MB max
            'is_public' => 'boolean'
        ]);

        $filePath = null;
        $fileType = null;
        $fileSize = null;

        if ($request->hasFile('file')) {
            $file = $request->file('file');
            $filePath = $file->store("projects/{$projectId}/materials", 'local');
            $fileType = $file->getClientOriginalExtension();
            $fileSize = $file->getSize() / 1024; // KB
        }

        $material = ProjectMaterial::create([
            'project_id' => $projectId,
            'uploaded_by' => auth()->id(),
            'title' => $request->title,
            'type' => $request->type,
            'content' => $request->content,
            'file_path' => $filePath,
            'file_type' => $fileType,
            'file_size' => $fileSize,
            'is_public' => $request->is_public ?? false
        ]);

        return response()->json(['message' => 'Materyal yüklendi', 'material' => $material], 201);
    }

    /**
     * Materyali İndir
     */
    public function download($id)
    {
        $material = ProjectMaterial::findOrFail($id);

        // Not: Burada user'in bu projede katılımcı olup olmadığı kontrol edilmelidir.
        if (!Storage::disk('local')->exists($material->file_path)) {
            return response()->json(['message' => 'Dosya bulunamadı.'], 404);
        }

        return Storage::disk('local')->download($material->file_path, $material->title . '.' . $material->file_type);
    }
}
