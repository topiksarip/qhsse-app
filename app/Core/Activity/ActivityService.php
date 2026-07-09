<?php

namespace App\Core\Activity;

use App\Models\Core\Activity\ActivityLog;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

class ActivityService
{
    public function log(
        string $moduleName,
        int $referenceId,
        string $event,
        ?string $description = null,
        ?User $actor = null,
        array $properties = [],
    ): ActivityLog {
        $actor ??= Auth::user();

        return ActivityLog::create([
            'module_name' => $moduleName,
            'reference_id' => $referenceId,
            'event' => $event,
            'description' => $description,
            'actor_id' => $actor?->id,
            'actor_name' => $actor?->name,
            'properties' => $properties,
        ]);
    }
}
