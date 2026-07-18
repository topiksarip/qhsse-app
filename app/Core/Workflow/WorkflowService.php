<?php

namespace App\Core\Workflow;

use App\Core\Activity\ActivityService;
use App\Core\Audit\AuditService;
use App\Models\Core\Workflow\WorkflowDefinition;
use App\Models\Core\Workflow\WorkflowHistory;
use App\Models\Core\Workflow\WorkflowInstance;
use App\Models\Core\Workflow\WorkflowTransition;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\UnauthorizedException;
use RuntimeException;

class WorkflowService
{
    public function start(string $moduleName, int $referenceId, ?User $actor = null, array $metadata = []): WorkflowInstance
    {
        return DB::transaction(function () use ($moduleName, $referenceId, $actor, $metadata): WorkflowInstance {
            $definition = WorkflowDefinition::query()
                ->where('module_name', $moduleName)
                ->where('is_active', true)
                ->firstOrFail();

            $instance = WorkflowInstance::query()->firstOrCreate(
                ['module_name' => $moduleName, 'reference_id' => $referenceId],
                [
                    'workflow_definition_id' => $definition->id,
                    'current_status' => $definition->initial_status,
                    'started_by' => $actor?->id,
                ],
            );

            if ($instance->wasRecentlyCreated) {
                $this->recordHistory($instance, null, $definition->initial_status, 'start', 'Start Workflow', null, $actor, $metadata);
            }

            return $instance;
        });
    }

    public function transition(
        string $moduleName,
        int $referenceId,
        string $actionKey,
        ?User $actor = null,
        ?string $reason = null,
        array $metadata = [],
    ): WorkflowInstance {
        return DB::transaction(function () use ($moduleName, $referenceId, $actionKey, $actor, $reason, $metadata): WorkflowInstance {
            $instance = WorkflowInstance::query()
                ->with('definition')
                ->where('module_name', $moduleName)
                ->where('reference_id', $referenceId)
                ->lockForUpdate()
                ->first();

            if (! $instance) {
                $instance = $this->start($moduleName, $referenceId, $actor);
                $instance->refresh()->load('definition');
                $instance = WorkflowInstance::query()->whereKey($instance->id)->lockForUpdate()->first();
                $instance->load('definition');
            }

            $transition = WorkflowTransition::query()
                ->where('workflow_definition_id', $instance->workflow_definition_id)
                ->where('from_status', $instance->current_status)
                ->where('action_key', $actionKey)
                ->where('is_active', true)
                ->first();

            if (! $transition) {
                throw new RuntimeException("Invalid workflow transition [{$actionKey}] from [{$instance->current_status}].");
            }

            if ($transition->requires_reason && blank($reason)) {
                throw new RuntimeException("Reason is required for workflow action [{$actionKey}].");
            }

            if ($transition->required_permission && ! $actor?->hasPermissionTo($transition->required_permission, 'web')) {
                throw new UnauthorizedException("Missing permission [{$transition->required_permission}].");
            }

            $fromStatus = $instance->current_status;
            $instance->update([
                'current_status' => $transition->to_status,
                'completed_at' => $this->isTerminalStatus($transition->to_status) ? now() : null,
            ]);

            $this->recordHistory(
                $instance,
                $fromStatus,
                $transition->to_status,
                $transition->action_key,
                $transition->action_label,
                $reason,
                $actor,
                $metadata,
            );

            app(AuditService::class)->workflow(
                'workflow.transitioned',
                $instance->module_name,
                $instance->reference_id,
                ['status' => $fromStatus],
                ['status' => $transition->to_status, 'action_key' => $transition->action_key],
                $actor,
                ['reason' => $reason],
            );

            app(ActivityService::class)->log(
                $instance->module_name,
                $instance->reference_id,
                'workflow.transitioned',
                $transition->action_label,
                $actor,
                ['from_status' => $fromStatus, 'to_status' => $transition->to_status, 'action_key' => $transition->action_key],
            );

            return $instance->refresh();
        });
    }

    public function availableTransitions(WorkflowInstance $instance): array
    {
        return WorkflowTransition::query()
            ->where('workflow_definition_id', $instance->workflow_definition_id)
            ->where('from_status', $instance->current_status)
            ->where('is_active', true)
            ->orderBy('id')
            ->get()
            ->all();
    }

    /**
     * Build a workflow payload for a module reference.
     * Returns current status + ordered available transitions + history.
     */
    public function getWorkflow(string $moduleName, int $referenceId): array
    {
        $instance = WorkflowInstance::query()
            ->with(['histories.actor', 'definition'])
            ->where('module_name', $moduleName)
            ->where('reference_id', $referenceId)
            ->first();

        if (! $instance) {
            return [
                'current_status' => null,
                'available_transitions' => [],
                'history' => [],
            ];
        }

        return [
            'current_status' => $instance->current_status,
            'available_transitions' => $this->availableTransitions($instance),
            'history' => $instance->histories
                ->sortBy('created_at')
                ->map(fn ($h) => [
                    'id' => $h->id,
                    'from_status' => $h->from_status,
                    'to_status' => $h->to_status,
                    'action_key' => $h->action_key,
                    'action_label' => $h->action_label,
                    'reason' => $h->reason,
                    'actor_name' => $h->actor?->name,
                    'created_at' => $h->created_at?->toDateTimeString(),
                ])
                ->all(),
        ];
    }

    private function recordHistory(
        WorkflowInstance $instance,
        ?string $fromStatus,
        string $toStatus,
        string $actionKey,
        string $actionLabel,
        ?string $reason,
        ?User $actor,
        array $metadata,
    ): WorkflowHistory {
        return WorkflowHistory::create([
            'workflow_instance_id' => $instance->id,
            'module_name' => $instance->module_name,
            'reference_id' => $instance->reference_id,
            'from_status' => $fromStatus,
            'to_status' => $toStatus,
            'action_key' => $actionKey,
            'action_label' => $actionLabel,
            'reason' => $reason,
            'actor_id' => $actor?->id,
            'metadata' => $metadata,
        ]);
    }

    private function isTerminalStatus(string $status): bool
    {
        return in_array($status, ['closed', 'rejected', 'obsolete', 'cancelled'], true);
    }
}
