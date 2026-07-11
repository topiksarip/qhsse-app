<?php

namespace Database\Seeders;

use App\Models\Core\Notifications\NotificationTemplate;
use App\Models\Core\Workflow\WorkflowDefinition;
use Illuminate\Database\Seeder;

class InvestigationSeeder extends Seeder
{
    public function run(): void
    {
        $definition = WorkflowDefinition::updateOrCreate(
            ['module_name' => 'investigation'],
            [
                'code' => 'INVESTIGATION_WORKFLOW',
                'name' => 'Investigation Workflow',
                'initial_status' => 'draft',
                'is_active' => true,
            ],
        );

        // draft → in_progress (start)
        $definition->transitions()->updateOrCreate(
            ['from_status' => 'draft', 'action_key' => 'start'],
            [
                'to_status' => 'in_progress',
                'action_label' => 'Mulai Investigasi',
                'requires_reason' => false,
                'required_permission' => 'investigation.reports.submit',
                'is_active' => true,
            ],
        );

        // in_progress → completed (complete, requires reason)
        $definition->transitions()->updateOrCreate(
            ['from_status' => 'in_progress', 'action_key' => 'complete'],
            [
                'to_status' => 'completed',
                'action_label' => 'Selesaikan Investigasi',
                'requires_reason' => true,
                'required_permission' => 'investigation.reports.close',
                'is_active' => true,
            ],
        );

        // draft → cancelled (cancel)
        $definition->transitions()->updateOrCreate(
            ['from_status' => 'draft', 'action_key' => 'cancel'],
            [
                'to_status' => 'cancelled',
                'action_label' => 'Batalkan Investigasi',
                'requires_reason' => true,
                'required_permission' => 'investigation.reports.update',
                'is_active' => true,
            ],
        );

        // in_progress → cancelled (cancel)
        $definition->transitions()->updateOrCreate(
            ['from_status' => 'in_progress', 'action_key' => 'cancel'],
            [
                'to_status' => 'cancelled',
                'action_label' => 'Batalkan Investigasi',
                'requires_reason' => true,
                'required_permission' => 'investigation.reports.update',
                'is_active' => true,
            ],
        );

        // Notification templates
        foreach ($this->templates() as $template) {
            NotificationTemplate::updateOrCreate(
                ['type' => $template['type']],
                $template,
            );
        }
    }

    private function templates(): array
    {
        return [
            [
                'type' => 'investigation.started',
                'title_template' => 'Investigasi dimulai: {{investigation_number}}',
                'message_template' => '{{actor_name}} memulai investigasi {{investigation_number}} - {{title}}.',
                'channels' => ['in_app'],
                'is_active' => true,
            ],
            [
                'type' => 'investigation.completed',
                'title_template' => 'Investigasi selesai: {{investigation_number}}',
                'message_template' => 'Investigasi {{investigation_number}} telah diselesaikan. Alasan: {{reason}}',
                'channels' => ['in_app'],
                'is_active' => true,
            ],
            [
                'type' => 'investigation.cancelled',
                'title_template' => 'Investigasi dibatalkan: {{investigation_number}}',
                'message_template' => 'Investigasi {{investigation_number}} dibatalkan. Alasan: {{reason}}',
                'channels' => ['in_app'],
                'is_active' => true,
            ],
        ];
    }
}
