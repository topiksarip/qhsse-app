<?php

namespace App\Console\Commands;

use App\Core\Notifications\NotificationService;
use App\Models\Modules\DocumentControl\ControlledDocument;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;

class CheckDocumentExpiry extends Command
{
    protected $signature = 'documents:check-expiry';

    protected $description = 'Send reminders for effective documents approaching review or expiry';

    public function handle(NotificationService $notifications): int
    {
        $managers = User::role('QHSSE Manager')->where('is_active', true)->get();

        foreach ([30, 7, 1] as $days) {
            $target = today()->addDays($days)->toDateString();

            foreach (['review_date', 'expiry_date'] as $dueField) {
                ControlledDocument::query()
                    ->with('owner')
                    ->where('status', 'effective')
                    ->whereDate($dueField, $target)
                    ->each(function (ControlledDocument $document) use ($notifications, $managers, $days, $target, $dueField): void {
                        $this->recipients($document, $managers)->each(function (User $recipient) use ($notifications, $document, $days, $target, $dueField): void {
                            $idempotencyKey = hash('sha256', implode('|', [
                                'document.expiry_reminder',
                                $recipient->id,
                                $document->id,
                                $dueField,
                                $target,
                                $days,
                            ]));

                            $notifications->notify(
                                $recipient,
                                'document.expiry_reminder',
                                [
                                    'document_number' => $document->document_number,
                                    'title' => $document->title,
                                    'days' => $days,
                                    'due_date' => $target,
                                    'due_field' => $dueField,
                                ],
                                moduleName: 'document',
                                referenceId: $document->id,
                                actionUrl: route('document.control.show', $document, false),
                                data: [
                                    'threshold_days' => $days,
                                    'due_field' => $dueField,
                                    'due_date' => $target,
                                ],
                                idempotencyKey: $idempotencyKey,
                            );
                        });
                    });
            }
        }

        $this->info('Document review and expiry reminders checked.');

        return self::SUCCESS;
    }

    /** @param Collection<int, User> $managers */
    private function recipients(ControlledDocument $document, Collection $managers): Collection
    {
        return $managers
            ->when($document->owner, fn (Collection $recipients) => $recipients->push($document->owner))
            ->unique('id')
            ->values();
    }
}
