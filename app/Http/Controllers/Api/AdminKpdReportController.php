<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\KpdReport;
use App\Models\User;
use Illuminate\Support\Facades\Storage;

class AdminKpdReportController extends Controller
{
    /**
     * Tüm KPD raporlarını listele
     */
    public function index()
    {
        $reports = KpdReport::with('user:id,name,email')->latest()->get();
        return response()->json($reports);
    }

    /**
     * Yeni KPD raporu yükle
     */
    public function store(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'title' => 'required|string|max:255',
            'file' => 'required|file|mimes:pdf,doc,docx|max:5120', // 5MB limit
            'notes' => 'nullable|string'
        ]);

        $user = User::findOrFail($request->user_id);
        
        $file = $request->file('file');
        $fileName = time() . '_' . $file->getClientOriginalName();
        $filePath = $file->storeAs("kpd_reports/user_{$user->id}", $fileName, 'local');

        $report = KpdReport::create([
            'user_id' => $user->id,
            'title' => $request->title,
            'file_path' => $filePath,
            'notes' => $request->notes
        ]);

        return response()->json([
            'message' => 'KPD Raporu başarıyla yüklendi.',
            'report' => $report->load('user:id,name,email')
        ], 201);
    }

    /**
     * Raporu güvenli bir şekilde indir
     */
    public function download($id)
    {
        $report = KpdReport::findOrFail($id);
        
        if (!Storage::disk('local')->exists($report->file_path)) {
            return response()->json(['message' => 'Dosya bulunamadı.'], 404);
        }

        return Storage::disk('local')->download($report->file_path, $report->title . '.' . pathinfo($report->file_path, PATHINFO_EXTENSION));
    }

    /**
     * Raporu sil
     */
    public function destroy($id)
    {
        $report = KpdReport::findOrFail($id);
        
        if (Storage::disk('local')->exists($report->file_path)) {
            Storage::disk('local')->delete($report->file_path);
        }
        
        $report->delete();

        return response()->json(['message' => 'Rapor başarıyla silindi.']);
    }
}
