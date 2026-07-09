<?php

namespace Database\Factories\Core\Workflow;

use App\Models\Core\Workflow\WorkflowDefinition;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<WorkflowDefinition> */
class WorkflowDefinitionFactory extends Factory
{
    protected $model = WorkflowDefinition::class;

    public function definition(): array
    {
        $module = 'core.'.$this->faker->unique()->slug(2);

        return [
            'module_name' => $module,
            'code' => strtoupper(str_replace('.', '_', $module)),
            'name' => $this->faker->words(2, true).' Workflow',
            'initial_status' => 'draft',
            'is_active' => true,
        ];
    }
}
