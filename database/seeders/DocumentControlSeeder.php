<?php

namespace Database\Seeders;

use App\Models\Core\Notifications\NotificationTemplate;
use Illuminate\Database\Seeder;

class DocumentControlSeeder extends Seeder
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
            $this->template('document.submitted', 'Dokumen menunggu review: {{document_number}}', '{{actor_name}} mengirim {{document_number}} — {{title}} untuk ditinjau.'),
            $this->template('document.approved', 'Dokumen disetujui: {{document_number}}', '{{document_number}} — {{title}} telah disetujui.'),
            $this->template('document.rejected', 'Dokumen ditolak: {{document_number}}', '{{document_number}} ditolak. Alasan: {{reason}}'),
            $this->template('document.effective', 'Dokumen efektif: {{document_number}}', '{{document_number}} — {{title}} mulai berlaku efektif.'),
            $this->template('document.obsolete', 'Dokumen obsolete: {{document_number}}', '{{document_number}} tidak berlaku lagi. Alasan: {{reason}}'),
            $this->template('document.expiry_reminder', 'Review dokumen {{document_number}} dalam {{days}} hari', '{{document_number}} — {{title}} memerlukan review/akan kedaluwarsa pada {{due_date}}.'),
        ];
    }

    private function template(string $type, string $title, string $message): array
    {
        return [
            'type' => $type,
            'title_template' => $title,
            'message_template' => $message,
            'channels' => ['in_app'],
            'is_active' => true,
        ];
    }
}
