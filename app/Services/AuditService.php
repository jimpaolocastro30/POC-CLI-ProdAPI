<?php

namespace App\Services;

use App\Models\AuditLog;
use Illuminate\Support\Facades\Request;

class AuditService
{
    public function log(
        ?int $userId,
        string $action,
        string $module,
        ?array $oldValue = null,
        ?array $newValue = null,
    ): AuditLog {
        return AuditLog::create([
            'user_id' => $userId,
            'action' => $action,
            'module' => $module,
            'old_value' => $oldValue,
            'new_value' => $newValue,
            'ip_address' => Request::ip(),
            'created_at' => now(),
        ]);
    }
}
