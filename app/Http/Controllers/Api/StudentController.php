<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\ProjectMaterial;
use App\Models\Application;
use App\Models\KpdReport;
use App\Models\Badge;

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
            ->with('project')
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
     * KPD Raporlarım: Gizli psikolojik raporlar
     */
    public function getReports()
    {
        $user = auth()->user();
        $reports = KpdReport::where('user_id', $user->id)->latest()->get();
        return response()->json($reports);
    }

    /**
     * Sertifikalarım: Tamamlanan/Mezun olunan projelerin listesi
     */
    public function getCertificates()
    {
        $user = auth()->user();
        
        // Bu örnekte mezuniyet statüsü henüz tam oturmadıysa, 
        // kabul edildiği projeleri listeliyoruz (İleride 'graduated' filtresi eklenebilir)
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

        if (!\Illuminate\Support\Facades\Storage::exists($report->file_path)) {
            return response()->json(['message' => 'Rapor dosyası bulunamadı.'], 404);
        }

        return \Illuminate\Support\Facades\Storage::download($report->file_path, "KADEME_Rapor_{$report->id}.pdf");
    }
}
