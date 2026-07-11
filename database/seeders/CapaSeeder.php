<?php

namespace Database\Seeders;

use App\Models\Core\Notifications\NotificationTemplate;
use Illuminate\Database\Seeder;

class CapaSeeder extends Seeder
{
    public function run(): void
    {
        foreach ($this->templates() as $template) {
            NotificationTemplate::updateOrCreate(['type' => $template['type']], $template);
        }
    }

    private function templates(): array
    {
        return [
            [
                'type' => 'capa.assigned',
                'title_template' => 'CAPA baru ditugaskan: {{action_number}}',
                'message_template' => '{{actor_name}} menugaskan Anda CAPA {{action_number}} - {{title}}.',
                'channels' => ['in_app'],
                'is_active' => true,
            ],
            [
                'type' => 'capa.closed',
                'title_template' => 'CAPA ditutup: {{action_number}}',
                'message_template' => 'CAPA {{action_number}} telah diverifikasi & ditutup. Catatan: {{reason}}',
                'channels' => ['in_app'],
                'is_active' => true,
            ],
            [
                'type' => 'capa.rejected',
                'title_template' => 'CAPA ditolak: {{action_number}}',
                'message_template' => 'CAPA {{action_number}} ditolak. Alasan: {{reason}}',
                'channels' => ['in_app'],
                'is_active' => true,
            ],
        ];
    }
}
