<?php

namespace App\Console\Commands;

use App\Core\Notifications\NotificationService;
use App\Models\Modules\LegalCompliance\LegalObligation;
use App\Models\User;
use Illuminate\Console\Command;

/**
 * WORKFLOW.md §6: alert overdue / due-soon legal obligations.
 *
 * Notifies register owner + QHSSE Manager/Officer per register site.
 * Anti-duplicate via idempotencyKey = "legal.obligation.overdue.{$id}.{$date}".
 */
class CheckOverdueObligations extends Command
{
    protected $signature = 'legal:check-overdue';
    protected $description = 'Notify overdue and due-soon legal obligations';

    public function __construct(protected NotificationService $notificationService)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $today = now()->toDateString();

        $overdue = LegalObligation::overdue()->with('legalRegister')->get();
        $dueSoon = LegalObligation::dueSoon(7)->with('legalRegister')->get();

        $sent = 0;

        foreach ($overdue as $obligation) {
            $sent += $this->notifyObligation($obligation, 'legal.obligation.overdue', $today);
        }

        foreach ($dueSoon as $obligation) {
            $sent += $this->notifyObligation($obligation, 'legal.obligation.due_soon', $today);
        }

        $this->info("Legal overdue check complete. Notifications sent: {$sent}");

        return self::SUCCESS;
    }

    protected function notifyObligation(LegalObligation $obligation, string $type, string $date): int
    {
        $register = $obligation->legalRegister;
        if (! $register) {
            return 0;
        }

        $idempotencyKey = "{$type}.{$obligation->id}.{$date}";

        $recipients = User::query()
            ->where('is_active', true)
            ->where(function ($q) use ($register) {
                $q->where('id', $register->owner_id)
                  ->orWhereHas('roles', fn ($r) => $r->whereIn('name', ['QHSSE Manager', 'QHSSE Officer']));
            })
            ->get();

        if ($recipients->isEmpty()) {
            return 0;
        }

        $context = [
            'obligation_id' => $obligation->id,
            'obligation_description' => $obligation->obligation_description,
            'register_id' => $register->id,
            'register_title' => $register->title ?? null,
            'next_due' => $obligation->next_due?->toDateString(),
            'type' => $type,
        ];

        $actionUrl = route('legal.registers.show', $register);

        foreach ($recipients as $recipient) {
            $this->notificationService->notify(
                recipient: $recipient,
                type: $type,
                context: $context,
                moduleName: 'legal',
                referenceId: $obligation->id,
                actionUrl: $actionUrl,
                data: ['context' => $context],
                idempotencyKey: "{$idempotencyKey}.{$recipient->id}",
            );
        }

        return $recipients->count();
    }
}
