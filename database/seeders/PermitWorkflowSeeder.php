<?php

namespace Database\Seeders;

use App\Models\Core\Workflow\WorkflowDefinition;
use Illuminate\Database\Seeder;

class PermitWorkflowSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $definition = WorkflowDefinition::create([
            'module_name' => 'permit',
            'code' => 'PERMIT_WORKFLOW',
            'name' => 'Permit to Work Workflow',
            'initial_status' => 'draft',
            'is_active' => true,
        ]);

        $transitions = [
            ['from_status' => 'draft', 'to_status' => 'submitted', 'action_key' => 'submit', 'action_label' => 'Submit', 'requires_reason' => false, 'required_permission' => 'permit.work.submit'],
            ['from_status' => 'submitted', 'to_status' => 'under_review', 'action_key' => 'review', 'action_label' => 'Start Review', 'requires_reason' => false, 'required_permission' => 'permit.work.review'],
            ['from_status' => 'under_review', 'to_status' => 'approved', 'action_key' => 'approve', 'action_label' => 'Approve', 'requires_reason' => false, 'required_permission' => 'permit.work.approve'],
            ['from_status' => 'approved', 'to_status' => 'active', 'action_key' => 'activate', 'action_label' => 'Activate', 'requires_reason' => false, 'required_permission' => 'permit.work.approve'],
            ['from_status' => 'active', 'to_status' => 'closed', 'action_key' => 'close', 'action_label' => 'Close', 'requires_reason' => true, 'required_permission' => 'permit.work.close'],
            ['from_status' => 'submitted', 'to_status' => 'rejected', 'action_key' => 'reject', 'action_label' => 'Reject', 'requires_reason' => true, 'required_permission' => 'permit.work.review'],
            ['from_status' => 'under_review', 'to_status' => 'rejected', 'action_key' => 'reject', 'action_label' => 'Reject', 'requires_reason' => true, 'required_permission' => 'permit.work.review'],
        ];

        foreach ($transitions as $transition) {
            $definition->transitions()->create($transition);
        }
    }
}
