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
        ];
    }
}
