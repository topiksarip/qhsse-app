<?php

namespace App\Modules\Capa;

use App\Core\Activity\ActivityService;
use App\Core\Audit\AuditService;
use App\Core\Notifications\NotificationService;
use App\Core\Numbering\NumberingService;
use App\Core\Workflow\WorkflowService;
use App\Models\Modules\Capa\CapaAction;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Creates CAPA actions from any source module (incident, inspection, APD inspection, etc.)
 * with full numbering, workflow, audit, activity and notification — mirroring CapaActionController::store.
 */
class CapaService
{
    public function __construct(
        private readonly NumberingService $numberingService,
        private readonly WorkflowService $workflowService,
        private readonly AuditService $auditService,
        private readonly ActivityService $activityService,
        private readonly NotificationService $notificationService,
    ) {}

    /**
     * @param  array{title:string, description:string, site_id:int, department_id?:?int, assigned_to:int, priority_id:int, due_date?:?string, severity_id?:?int, source_type?:?string}  $data
     */
    public function escalateFrom(string $sourceModule, int $sourceId, array $data, User $actor): CapaAction
    {
        return DB::transaction(function () use ($sourceModule, $sourceId, $data, $actor) {
            $action = CapaAction::create([
                'action_number' => 'TEMP-' . uniqid(),
                'title' => $data['title'],
                'description' => $data['description'],
                'source_module' => $sourceModule,
                'source_reference_id' => $sourceId,
                'source_type' => $data['source_type'] ?? null,
                'site_id' => $data['site_id'],
                'department_id' => $data['department_id'] ?? null,
                'assigned_to' => $data['assigned_to'],
                'assigned_by' => $actor->id,
                'assigned_at' => now(),
                'due_date' => $data['due_date'] ?? null,
                'severity_id' => $data['severity_id'] ?? null,
                'priority_id' => $data['priority_id'],
                'status' => 'open',
            ]);

            $generated = $this->numberingService->generate(
                moduleName: 'capa',
                actor: $actor,
                referenceType: CapaAction::class,
                referenceId: $action->id,
            );
            $action->update(['action_number' => $generated->number]);

            $this->workflowService->start('capa', $action->id, $actor);
            $this->auditService->created($action, $actor, 'capa', $action->id);
            $this->activityService->log('capa', $action->id, 'capa.created', 'CAPA action dibuat dari ' . $sourceModule, $actor);

            $assignee = User::find($data['assigned_to']);
            if ($assignee) {
                $this->notificationService->notify(
                    $assignee,
                    'capa.assigned',
                    ['action_number' => $action->action_number, 'title' => $action->title, 'actor_name' => $actor->name],
                    $actor,
                    'capa',
                    $action->id,
                    route('capa.actions.show', $action),
                );
            }

            return $action;
        });
    }
}
