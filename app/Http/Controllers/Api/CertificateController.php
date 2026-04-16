<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Project;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;

class CertificateController extends Controller
{
    /**
     * Katılımcı için Mezuniyet Sertifikası Üretir
     */
    public function generate(Request $request, $projectId, $userId)
    {
        $project = Project::findOrFail($projectId);
        $user = User::findOrFail($userId);

        // Normally, you would check if user is marked as 'graduated'
        // \App\Models\ParticipantProfile::where('user_id', $userId)->where('status', 'graduated')->firstOrFail();

        $pdf = Pdf::loadView('certificate', compact('user', 'project'))
                    ->setPaper('a4', 'landscape');

        // İsterse indirir, isterse ekranda gösteririz
        return $pdf->stream('KADEME_Sertifika_'.$user->name.'.pdf');
    }
}
