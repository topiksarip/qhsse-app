<?php

namespace Database\Seeders;

use App\Models\Core\Notifications\NotificationTemplate;
use App\Models\Core\Workflow\WorkflowDefinition;
use Illuminate\Database\Seeder;

class InspectionSeeder extends Seeder
{
    public function run(): void
    {
        $definition = WorkflowDefinition::updateOrCreate(
            ['module_name' => 'inspection'],
            ['code' => 'INSPECTION_WORKFLOW', 'name' => 'Inspection Workflow', 'initial_status' => 'pending', 'is_active' => true],
        );

        $definition->transitions()->updateOrCreate(
            ['from_status' => 'pending', 'action_key' => 'start'],
            ['to_status' => 'in_progress', 'action_label' => 'Mulai Inspeksi', 'requires_reason' => false, 'required_permission' => 'inspection.checklists.execute', 'is_active' => true],
        );

        $definition->transitions()->updateOrCreate(
            ['from_status' => 'in_progress', 'action_key' => 'complete'],
            ['to_status' => 'completed', 'action_label' => 'Selesaikan Inspeksi', 'requires_reason' => false, 'required_permission' => 'inspection.checklists.execute', 'is_active' => true],
        );

        foreach ($this->templates() as $t) {
            NotificationTemplate::updateOrCreate(['type' => $t['type']], $t);
        }
    }

    private function templates(): array
    {
        return [
            ['type' => 'inspection.assigned', 'title_template' => 'Inspeksi ditugaskan: {{inspection_number}}', 'message_template' => 'Anda ditugaskan inspeksi {{inspection_number}}.', 'channels' => ['in_app'], 'is_active' => true],
            ['type' => 'inspection.completed', 'title_template' => 'Inspeksi selesai: {{inspection_number}}', 'message_template' => 'Inspeksi {{inspection_number}} telah diselesaikan. Result: {{overall_result}}', 'channels' => ['in_app'], 'is_active' => true],
        ];
    }
}
