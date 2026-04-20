<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\ProjectMaterial;
use App\Models\Application;
use App\Models\KpdReport;
use App\Models\Badge;
use Illuminate\Support\Facades\Storage;

class StudentController extends Controller
{
    /**
     * Dijital Bohça: Katılımcının kabul edildiği projelerdeki tüm içerikler
     */
    public function getBundle()
    {
        $user = auth()->user();
        
        // Katılımcının kabul edildiği Proje ID'lerini al
        $projectIds = Application::where('user_id', $user->id)
            ->where('status', 'accepted')
            ->pluck('project_id');

        $materials = ProjectMaterial::whereIn('project_id', $projectIds)
            ->with('project:id,name')
            ->latest()
            ->get();

        return response()->json($materials);
    }

    /**
     * Rozetlerim: Kazanılan rozetler
     */
    public function getBadges()
    {
        $user = auth()->user();
        $badges = $user->badges()->get();
        return response()->json($badges);
    }

    /**
     * KPD Raporlarım: Psikolojik raporlar
     */
    public function getReports()
    {
        $user = auth()->user();
        $reports = KpdReport::where('user_id', $user->id)->latest()->get();
        return response()->json($reports);
    }

    /**
     * Sertifikalarım: Tamamlanan projelerin listesi
     */
    public function getCertificates()
    {
        $user = auth()->user();
        
        $projects = Application::where('user_id', $user->id)
            ->where('status', 'accepted')
            ->with('project')
            ->get()
            ->pluck('project');

        return response()->json($projects);
    }

    /**
     * KPD Raporunu Güvenli İndir
     */
    public function downloadReport($id)
    {
        $user = auth()->user();
        $report = KpdReport::where('id', $id)->where('user_id', $user->id)->firstOrFail();

        if (!Storage::exists($report->file_path)) {
            return response()->json(['message' => 'Rapor dosyası bulunamadı.'], 404);
        }

        return Storage::download($report->file_path, "KADEME_Rapor_{$report->id}.pdf");
    }
}
