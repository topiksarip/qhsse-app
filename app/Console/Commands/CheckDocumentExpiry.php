<?php

namespace App\Console\Commands;

use App\Core\Notifications\NotificationService;
use App\Models\Core\Notifications\CoreNotification;
use App\Models\Modules\DocumentControl\ControlledDocument;
use Illuminate\Console\Command;

class CheckDocumentExpiry extends Command
{
    protected $signature = 'documents:check-expiry';

    protected $description = 'Send reminders for effective documents approaching review or expiry';

    public function handle(NotificationService $notifications): int
    {
        foreach ([30, 7, 1] as $days) {
            $target = today()->addDays($days)->toDateString();

            ControlledDocument::query()
                ->with('owner')
                ->where('status', 'effective')
                ->where(function ($query) use ($target): void {
                    $query->whereDate('review_date', $target)->orWhereDate('expiry_date', $target);
                })
                ->each(function (ControlledDocument $document) use ($notifications, $days, $target): void {
                    if (! $document->owner || CoreNotification::query()
                        ->where('recipient_id', $document->owner_id)
                        ->where('type', 'document.expiry_reminder')
                        ->where('module_name', 'document')
                        ->where('reference_id', $document->id)
                        ->whereDate('created_at', today())
                        ->exists()) {
                        return;
                    }

                    $notifications->notify(
                        $document->owner,
                        'document.expiry_reminder',
                        [
                            'document_number' => $document->document_number,
                            'title' => $document->title,
                            'days' => $days,
                            'due_date' => $target,
                        ],
                        moduleName: 'document',
                        referenceId: $document->id,
                        actionUrl: route('document.control.show', $document, false),
                        data: ['threshold_days' => $days],
                    );
                });
        }

        $this->info('Document review and expiry reminders checked.');

        return self::SUCCESS;
    }
}
