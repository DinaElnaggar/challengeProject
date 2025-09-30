<?php

namespace App\Support;

use App\Models\AuditLog;

class AuditLogger
{
    public static function log(string $action, string $resourceType, ?int $resourceId, array $oldValues = null, array $newValues = null): void
    {
        $user = auth('api')->user();
        AuditLog::create([
            'actor_id' => $user?->id,
            'action' => $action,
            'resource_type' => $resourceType,
            'resource_id' => $resourceId,
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'ip_address' => request()->ip(),
            'user_agent' => (string) request()->userAgent(),
        ]);
    }
}

