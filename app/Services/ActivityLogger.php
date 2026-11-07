<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\User;

class ActivityLogger
{
    public function record(
        ?User $actor,
        string $action,
        string $targetType,
        ?int $targetId,
        ?int $departmentId = null,
    ): AuditLog {
        return AuditLog::create([
            'actor_id' => $actor?->id,
            'action' => $action,
            'target_type' => $targetType,
            'target_id' => $targetId,
            'department_id' => $departmentId,
        ]);
    }
}
