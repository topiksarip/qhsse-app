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
        ?string $event = null,
        ?string $description = null,
        User|int|array|null $actor = null,
        array $properties = [],
        ?string $action = null,
        ?int $userId = null,
        array $metadata = [],
        ?string $referenceType = null,
    ): ActivityLog {
        $event ??= $action ?? 'activity.logged';
        $properties = array_merge($properties, $metadata);

        if ($referenceType !== null) {
            $properties['reference_type'] = $referenceType;
        }
        if (is_array($actor)) {
            $properties = ['before' => $actor, 'after' => $properties];
            $actor = null;
        }
        if (is_int($actor)) {
            $actor = User::find($actor);
        }
        if (! $actor && $userId !== null) {
            $actor = User::find($userId);
        }
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
