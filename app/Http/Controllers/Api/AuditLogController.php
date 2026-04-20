<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\AuditLog;

class AuditLogController extends Controller
{
    /**
     * Tüm işlem loglarını listele (Sadece Admin)
     */
    public function index()
    {
        $logs = AuditLog::with('user:id,name')
            ->latest()
            ->paginate(30);

        return response()->json($logs);
    }
}
