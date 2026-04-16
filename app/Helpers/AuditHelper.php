<?php

namespace App\Helpers;

use App\Models\AuditLog;
use Illuminate\Support\Facades\Auth;

class AuditHelper
{
    /**
     * Kritik bir işlemi loglar (Section 11.13)
     */
    public static function log($action, $targetType, $targetId, $oldValues = null, $newValues = null, $description = null)
    {
        return AuditLog::create([
            'user_id' => Auth::id(),
            'action' => $action,
            'target_type' => $targetType,
            'target_id' => $targetId,
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'description' => $description,
            'ip_address' => request()->ip()
        ]);
    }
}
