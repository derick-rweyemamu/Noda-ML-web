<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\User;

class AuditService
{
    public static function log(?User $actor, string $action, string $target, string $details): void
    {
        AuditLog::create([
            'actor_id' => $actor?->id,
            'actor_name' => $actor?->name ?? 'System Scheduler',
            'actor_role' => $actor?->role ?? 'system',
            'action' => $action,
            'target' => $target,
            'details' => $details,
        ]);
    }
}
