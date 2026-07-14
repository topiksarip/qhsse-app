<?php

namespace App\Console\Commands;

use App\Core\Activity\ActivityService;
use App\Core\Notifications\NotificationService;
use App\Models\Modules\Asset\AssetCertificate;
use App\Models\User;
use App\Modules\Asset\AssetNotificationRecipients;
use Illuminate\Console\Command;

class CheckAssetCertificates extends Command
{
    protected $signature = 'assets:check-certificates';

    protected $description = 'Update asset certificate statuses and send expiry notifications';

    public function handle(
        NotificationService $notifications,
        AssetNotificationRecipients $recipients,
        ActivityService $activity,
    ): int {
        AssetCertificate::query()
            ->activeRecords()
            ->with(['asset' => fn ($q) => $q->where('status', '!=', 'decommissioned')])
            ->whereNotNull('expiry_date')
            ->whereHas('asset', fn ($q) => $q->where('status', '!=', 'decommissioned'))
            ->each(function (AssetCertificate $certificate) use ($notifications, $recipients, $activity): void {
                $previousStatus = $certificate->status;
                $certificate->updateStatus();
                $certificate->refresh();

                if ($certificate->status !== $previousStatus) {
                    $activity->log(
                        'asset',
                        $certificate->asset_id,
                        'asset.certificate.status_changed',
                        "Certificate {$certificate->certificate_number} changed to {$certificate->status}",
                        properties: ['certificate_id' => $certificate->id, 'old_status' => $previousStatus, 'new_status' => $certificate->status],
                    );
                }

                $type = match ($certificate->status) {
                    'expired' => 'asset.certificate.expired',
                    'expiring_critical' => 'asset.certificate.expiring_critical',
                    'expiring_soon' => 'asset.certificate.expiring_soon',
                    default => null,
                };

                if ($type === null) {
                    return;
                }

                $recipients->forCertificate($certificate->asset, $certificate->status)
                    ->each(function (User $recipient) use ($certificate, $notifications, $type): void {
                        $idempotencyKey = hash('sha256', implode('|', [
                            $type,
                            $recipient->id,
                            $certificate->id,
                            $certificate->expiry_date->toDateString(),
                        ]));

                        $notifications->notify(
                            $recipient,
                            $type,
                            [
                                'asset_number' => $certificate->asset->asset_number,
                                'certificate_number' => $certificate->certificate_number,
                                'expiry_date' => $certificate->expiry_date->toDateString(),
                            ],
                            moduleName: 'asset',
                            referenceId: $certificate->asset_id,
                            actionUrl: route('assets.certificates.show', [$certificate->asset_id, $certificate->id], false),
                            data: ['certificate_id' => $certificate->id, 'status' => $certificate->status],
                            idempotencyKey: $idempotencyKey,
                        );
                    });
            });

        $this->info('Asset certificate statuses and expiry notifications checked.');

        return self::SUCCESS;
    }
}
