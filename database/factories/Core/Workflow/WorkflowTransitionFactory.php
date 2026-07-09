<?php

namespace Database\Factories\Core\Workflow;

use App\Models\Core\Workflow\WorkflowDefinition;
use App\Models\Core\Workflow\WorkflowTransition;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<WorkflowTransition> */
class WorkflowTransitionFactory extends Factory
{
    protected $model = WorkflowTransition::class;

    public function definition(): array
    {
        return [
            'workflow_definition_id' => WorkflowDefinition::factory(),
            'from_status' => 'draft',
            'to_status' => 'submitted',
            'action_key' => 'submit',
            'action_label' => 'Submit',
            'requires_reason' => false,
            'required_permission' => null,
            'is_active' => true,
        ];
    }
}
