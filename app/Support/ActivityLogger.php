<?php

namespace App\Support;

use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class ActivityLogger
{
    public static function log(
        string $action,
        ?Model $subject = null,
        ?string $reason = null,
        array $properties = [],
        ?User $user = null,
    ): ActivityLog {
        return ActivityLog::query()->create([
            'user_id' => $user?->id ?? auth()->id(),
            'action' => $action,
            'subject_type' => $subject ? $subject::class : null,
            'subject_id' => $subject?->getKey(),
            'reason' => $reason,
            'properties' => $properties ?: null,
            'ip_address' => request()->ip(),
        ]);
    }
}
