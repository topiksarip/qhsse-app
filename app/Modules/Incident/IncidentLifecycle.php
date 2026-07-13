<?php

namespace App\Modules\Incident;

use App\Core\Activity\ActivityService;
use App\Core\Notifications\NotificationService;
use App\Core\Workflow\WorkflowService;
use App\Models\Modules\Incident\IncidentReport;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;

class IncidentLifecycle
{
    public function __construct(
        private readonly WorkflowService $workflow,
        private readonly ActivityService $activity,
        private readonly NotificationService $notifications,
    ) {}

    public function transition(
        IncidentReport $incident,
        User $actor,
        string $action,
        string $status,
        ?string $reason = null,
    ): void {
        DB::transaction(function () use ($incident, $actor, $action, $status, $reason): void {
            $this->workflow->transition('incident', $incident->id, $action, $actor, $reason);
            $incident->update(['status' => $status]);
            $this->activity->log(
                'incident',
                $incident->id,
                "incident.{$status}",
                $reason ? "Laporan {$status}: {$reason}" : "Laporan {$status}",
                $actor,
            );
            $this->sendNotification($incident, $actor, $action, $reason);
        });
    }

    private function sendNotification(IncidentReport $incident, User $actor, string $action, ?string $reason): void
    {
        if ($action === 'submit') {
            $recipients = $this->qhsseUsers();
            if ($recipients->isNotEmpty()) {
                $this->notifications->notifyMany($recipients, 'incident.submitted', [
                    'incident_number' => $incident->incident_number,
                    'title' => $incident->title,
                    'actor_name' => $actor->name,
                ], $actor, 'incident', $incident->id, route('incident.reports.show', $incident));
            }

            return;
        }

        $type = match ($action) {
            'review' => 'incident.reviewing',
            'reject' => 'incident.rejected',
            'close' => 'incident.closed',
            default => null,
        };

        if (! $type) {
            return;
        }

        $incident->loadMissing('reporter');
        if ($incident->reporter) {
            $this->notifications->notify($incident->reporter, $type, [
                'incident_number' => $incident->incident_number,
                'reason' => $reason,
                'actor_name' => $actor->name,
            ], $actor, 'incident', $incident->id, route('incident.reports.show', $incident));
        }
    }

    private function qhsseUsers()
    {
        $roleIds = Role::query()->whereIn('name', ['QHSSE Officer', 'QHSSE Manager'])->pluck('id');

        return User::query()
            ->where('is_active', true)
            ->whereHas('roles', fn ($roles) => $roles->whereIn('roles.id', $roleIds))
            ->get();
    }
}
