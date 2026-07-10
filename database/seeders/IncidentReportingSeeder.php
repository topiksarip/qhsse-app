<?php

namespace Database\Seeders;

use App\Models\Core\Notifications\NotificationTemplate;
use App\Models\Core\Workflow\WorkflowDefinition;
use Illuminate\Database\Seeder;

class IncidentReportingSeeder extends Seeder
{
    public function run(): void
    {
        // Add under_review → closed transition for Phase 1 simplicity
        $definition = WorkflowDefinition::where('module_name', 'incident')->first();

        if ($definition) {
            $definition->transitions()->updateOrCreate(
                ['from_status' => 'under_review', 'action_key' => 'close'],
                [
                    'to_status' => 'closed',
                    'action_key' => 'close',
                    'action_label' => 'Close',
                    'requires_reason' => true,
                    'required_permission' => 'incident.reports.close',
                    'is_active' => true,
                ],
            );
        }

        // Notification templates for incident events
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
                'type' => 'incident.submitted',
                'title_template' => 'Laporan insiden baru: {{incident_number}}',
                'message_template' => '{{actor_name}} mengirim laporan {{incident_number}} - {{title}} untuk review.',
                'channels' => ['in_app'],
                'is_active' => true,
            ],
            [
                'type' => 'incident.reviewing',
                'title_template' => 'Laporan {{incident_number}} sedang direview',
                'message_template' => 'Laporan Anda {{incident_number}} sedang dalam review oleh QHSSE team.',
                'channels' => ['in_app'],
                'is_active' => true,
            ],
            [
                'type' => 'incident.closed',
                'title_template' => 'Laporan {{incident_number}} ditutup',
                'message_template' => 'Laporan {{incident_number}} telah ditutup. Alasan: {{reason}}',
                'channels' => ['in_app'],
                'is_active' => true,
            ],
            [
                'type' => 'incident.rejected',
                'title_template' => 'Laporan {{incident_number}} ditolak',
                'message_template' => 'Laporan {{incident_number}} ditolak. Alasan: {{reason}}',
                'channels' => ['in_app'],
                'is_active' => true,
            ],
        ];
    }
}
