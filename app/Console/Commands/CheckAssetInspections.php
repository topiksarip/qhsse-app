<?php

namespace App\Console\Commands;

use App\Core\Notifications\NotificationService;
use App\Models\Modules\Asset\Asset;
use App\Models\Modules\Asset\AssetInspection;
use App\Models\User;
use App\Modules\Asset\AssetNotificationRecipients;
use Illuminate\Console\Command;

class CheckAssetInspections extends Command
{
    protected $signature = 'assets:check-inspections';

    protected $description = 'Send reminders for asset inspections due within seven days';

    public function handle(
        NotificationService $notifications,
        AssetNotificationRecipients $recipients,
    ): int {
        Asset::query()
            ->with(['inspections' => fn ($query) => $query
                ->activeRecords()
                ->orderByDesc('inspection_date')
                ->orderByDesc('id')])
            ->where('status', '!=', 'decommissioned')
            ->whereHas('inspections', fn ($q) => $q->activeRecords())
            ->each(function (Asset $asset) use ($notifications, $recipients): void {
                /** @var AssetInspection|null $latestInspection */
                $latestInspection = $asset->inspections->first();

                if ($latestInspection?->next_inspection_date === null
                    || $latestInspection->next_inspection_date->isAfter(today()->addDays(7))) {
                    return;
                }

                $dueDate = $latestInspection->next_inspection_date->toDateString();

                $recipients->forInspection($asset)
                    ->each(function (User $recipient) use ($asset, $dueDate, $notifications): void {
                        $idempotencyKey = hash('sha256', implode('|', [
                            'asset.inspection.due',
                            $recipient->id,
                            $asset->id,
                            $dueDate,
                        ]));

                        $notifications->notify(
                            $recipient,
                            'asset.inspection.due',
                            [
                                'asset_number' => $asset->asset_number,
                                'due_date' => $dueDate,
                            ],
                            moduleName: 'asset',
                            referenceId: $asset->id,
                            actionUrl: route('assets.show', $asset, false),
                            data: ['due_date' => $dueDate],
                            idempotencyKey: $idempotencyKey,
                        );
                    });
            });

        $this->info('Asset inspection due notifications checked.');

        return self::SUCCESS;
    }
}
