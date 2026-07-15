<?php

namespace App\Console\Commands;

use App\Core\Notifications\NotificationService;
use App\Models\Modules\Contractor\Contractor;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;

class CheckExpiringPrequalification extends Command
{
    protected $signature = 'contractor:check-prequalification-expiry';

    protected $description = 'Notify QHSSE team and contractor creator about prequalifications expiring within 30 days';

    public function handle(NotificationService $notifications): int
    {
        $days = 30;
        $today = today();
        $threshold = $today->copy()->addDays($days);

        $contractors = Contractor::query()
            ->where('is_prequalified', true)
            ->whereNotNull('prequalified_until')
            ->where('prequalified_until', '>', $today->toDateString())
            ->where('prequalified_until', '<=', $threshold->toDateString())
            ->get();

        if ($contractors->isEmpty()) {
            $this->info('No expiring prequalifications found.');

            return self::SUCCESS;
        }

        foreach ($contractors as $contractor) {
            $recipients = $this->recipients($contractor);

            $recipients->each(function (User $recipient) use ($notifications, $contractor, $days): void {
                $idempotencyKey = hash('sha256', implode('|', [
                    'contractor.expiring_soon',
                    $recipient->id,
                    $contractor->id,
                    $contractor->prequalified_until?->toDateString(),
                ]));

                $notifications->notify(
                    $recipient,
                    'contractor.expiring_soon',
                    [
                        'contractor_number' => $contractor->contractor_number,
                        'company_name' => $contractor->company_name,
                        'prequalified_until' => $contractor->prequalified_until?->toDateString(),
                        'days' => $days,
                    ],
                    moduleName: 'contractor',
                    referenceId: $contractor->id,
                    actionUrl: route('contractors.show', $contractor, false),
                    data: [
                        'threshold_days' => $days,
                        'prequalified_until' => $contractor->prequalified_until?->toDateString(),
                    ],
                    idempotencyKey: $idempotencyKey,
                );
            });
        }

        $this->info("Sent expiring-soon reminders for {$contractors->count()} contractor(s).");

        return self::SUCCESS;
    }

    /** @param Collection<int, User> $managers */
    private function recipients(Contractor $contractor): Collection
    {
        $recipients = User::role(['QHSSE Manager', 'QHSSE Officer'])
            ->where('is_active', true)
            ->get();

        if ($contractor->created_by) {
            $creator = User::find($contractor->created_by);
            if ($creator && !$recipients->contains('id', $creator->id)) {
                $recipients->push($creator);
            }
        }

        return $recipients->unique('id')->values();
    }
}
