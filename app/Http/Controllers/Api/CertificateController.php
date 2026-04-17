<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Project;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Services\QrService;

class CertificateController extends Controller
{
    protected $qrService;

    public function __construct(QrService $qrService)
    {
        $this->qrService = $qrService;
    }
    /**
     * Katılımcı için Mezuniyet Sertifikası Üretir
     */
    public function generate(Request $request, $projectId, $userId)
    {
        $project = Project::findOrFail($projectId);
        $user = User::findOrFail($userId);

        // Normally, you would check if user is marked as 'graduated'
        // \App\Models\ParticipantProfile::where('user_id', $userId)->where('status', 'graduated')->firstOrFail();

        $verifyUrl = url('/api/cv/' . ($user->participantProfile->uuid ?? $user->id));
        $qrUrl = $this->qrService->generateUrl($verifyUrl, 150);

        $pdf = Pdf::loadView('certificate', compact('user', 'project', 'qrUrl'))
                    ->setPaper('a4', 'landscape');

        // İsterse indirir, isterse ekranda gösteririz
        return $pdf->stream('KADEME_Sertifika_'.$user->name.'.pdf');
    }
}
