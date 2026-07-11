<?php

namespace Database\Seeders;

use App\Models\Core\Workflow\WorkflowDefinition;
use Illuminate\Database\Seeder;

class WorkflowSeeder extends Seeder
{
    public function run(): void
    {
        foreach ($this->definitions() as $definitionData) {
            $transitions = $definitionData['transitions'];
            unset($definitionData['transitions']);

            $definition = WorkflowDefinition::updateOrCreate(
                ['module_name' => $definitionData['module_name']],
                $definitionData,
            );

            foreach ($transitions as $transition) {
                $definition->transitions()->updateOrCreate(
                    ['from_status' => $transition['from_status'], 'action_key' => $transition['action_key']],
                    $transition,
                );
            }
        }
    }

    private function definitions(): array
    {
        return [
            [
                'module_name' => 'incident',
                'code' => 'INCIDENT_WORKFLOW',
                'name' => 'Incident Workflow',
                'initial_status' => 'draft',
                'is_active' => true,
                'transitions' => [
                    $this->transition('draft', 'submitted', 'submit', 'Submit', false, 'core.workflow.transition'),
                    $this->transition('submitted', 'under_review', 'review', 'Start Review', false, 'core.workflow.transition'),
                    $this->transition('under_review', 'investigation', 'investigate', 'Start Investigation', false, 'core.workflow.transition'),
                    $this->transition('under_review', 'action_open', 'open_action', 'Open Action', false, 'core.workflow.transition'),
                    $this->transition('investigation', 'action_open', 'open_action', 'Open Action', false, 'core.workflow.transition'),
                    $this->transition('action_open', 'closed', 'close', 'Close', true, 'core.workflow.transition'),
                    $this->transition('submitted', 'rejected', 'reject', 'Reject', true, 'core.workflow.transition'),
                    $this->transition('under_review', 'rejected', 'reject', 'Reject', true, 'core.workflow.transition'),
                ],
            ],
            [
                'module_name' => 'capa',
                'code' => 'CAPA_WORKFLOW',
                'name' => 'CAPA Workflow',
                'initial_status' => 'open',
                'is_active' => true,
                'transitions' => [
                    $this->transition('open', 'in_progress', 'start', 'Start', false, 'core.workflow.transition'),
                    $this->transition('in_progress', 'waiting_verification', 'submit_verification', 'Submit Verification', false, 'core.workflow.transition'),
                    $this->transition('waiting_verification', 'closed', 'verify_close', 'Verify and Close', true, 'core.workflow.transition'),
                    $this->transition('waiting_verification', 'rejected', 'reject', 'Reject', true, 'core.workflow.transition'),
                    $this->transition('rejected', 'in_progress', 'restart', 'Restart', false, 'core.workflow.transition'),
                ],
            ],
            [
                'module_name' => 'document',
                'code' => 'DOCUMENT_WORKFLOW',
                'name' => 'Document Workflow',
                'initial_status' => 'draft',
                'is_active' => true,
                'transitions' => [
                    $this->transition('draft', 'review', 'submit_review', 'Submit Review', false, 'core.workflow.transition'),
                    $this->transition('review', 'approved', 'approve', 'Approve', false, 'core.workflow.transition'),
                    $this->transition('approved', 'effective', 'make_effective', 'Make Effective', false, 'core.workflow.transition'),
                    $this->transition('effective', 'obsolete', 'obsolete', 'Mark Obsolete', true, 'core.workflow.transition'),
                    $this->transition('review', 'rejected', 'reject', 'Reject', true, 'core.workflow.transition'),
                    $this->transition('rejected', 'draft', 'revise', 'Revise', false, 'core.workflow.transition'),
                ],
            ],
            [
                'module_name' => 'audit',
                'code' => 'AUDIT_WORKFLOW',
                'name' => 'Audit Workflow',
                'initial_status' => 'planned',
                'is_active' => true,
                'transitions' => [
                    $this->transition('planned', 'in_progress', 'start', 'Start Audit', false, 'core.workflow.transition'),
                    $this->transition('in_progress', 'report_ready', 'generate_report', 'Generate Report', false, 'core.workflow.transition'),
                    $this->transition('report_ready', 'closed', 'close', 'Close Audit', false, 'core.workflow.transition'),
                ],
            ],
        ];
    }

    private function transition(string $from, string $to, string $action, string $label, bool $reason = false, ?string $permission = null): array
    {
        return [
            'from_status' => $from,
            'to_status' => $to,
            'action_key' => $action,
            'action_label' => $label,
            'requires_reason' => $reason,
            'required_permission' => $permission,
            'is_active' => true,
        ];
    }
}
