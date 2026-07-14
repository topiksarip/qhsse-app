<?php

namespace Database\Seeders;

use App\Models\Core\Notifications\NotificationTemplate;
use Illuminate\Database\Seeder;

class NotificationTemplateSeeder extends Seeder
{
    public function run(): void
    {
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
                'type' => 'core.test',
                'title_template' => 'Test notification for {{module_name}}',
                'message_template' => 'Reference #{{reference_id}} generated a test notification.',
                'channels' => ['in_app'],
                'is_active' => true,
            ],
            [
                'type' => 'comment.mentioned',
                'title_template' => '{{actor_name}} mentioned you',
                'message_template' => '{{module_name}} #{{reference_id}} has a new mention.',
                'channels' => ['in_app'],
                'is_active' => true,
            ],
            [
                'type' => 'workflow.transitioned',
                'title_template' => '{{module_name}} #{{reference_id}} moved to {{to_status}}',
                'message_template' => '{{actor_name}} performed {{action_key}}.',
                'channels' => ['in_app'],
                'is_active' => true,
            ],
            [
                'type' => 'asset.certificate.expiring_soon',
                'title_template' => 'Sertifikat aset {{asset_number}} segera kedaluwarsa',
                'message_template' => '{{certificate_number}} kedaluwarsa pada {{expiry_date}}.',
                'channels' => ['in_app'],
                'is_active' => true,
            ],
            [
                'type' => 'asset.certificate.expiring_critical',
                'title_template' => 'Sertifikat aset {{asset_number}} kritis',
                'message_template' => '{{certificate_number}} kedaluwarsa pada {{expiry_date}}.',
                'channels' => ['in_app'],
                'is_active' => true,
            ],
            [
                'type' => 'asset.certificate.expired',
                'title_template' => 'Sertifikat aset {{asset_number}} kedaluwarsa',
                'message_template' => '{{certificate_number}} telah kedaluwarsa pada {{expiry_date}}.',
                'channels' => ['in_app'],
                'is_active' => true,
            ],
            [
                'type' => 'asset.inspection.due',
                'title_template' => 'Inspeksi aset {{asset_number}} jatuh tempo',
                'message_template' => 'Inspeksi berikutnya dijadwalkan pada {{due_date}}.',
                'channels' => ['in_app'],
                'is_active' => true,
            ],
            [
                'type' => 'security.patrol.executed',
                'title_template' => 'Patroli Dimulai: {{patrol_number}}',
                'message_template' => '{{actor_name}} memulai patroli {{patrol_number}} di {{site_name}}.',
                'channels' => ['in_app'],
                'is_active' => true,
            ],
            [
                'type' => 'security.patrol.issue_found',
                'title_template' => 'Temuan Patroli: {{patrol_number}}',
                'message_template' => 'Issue ditemukan di checkpoint {{checkpoint}}: {{findings}}',
                'channels' => ['in_app'],
                'is_active' => true,
            ],
        ];
    }
}
