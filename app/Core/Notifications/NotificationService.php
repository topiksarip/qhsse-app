<?php

namespace App\Core\Notifications;

use App\Models\Core\Notifications\CoreNotification;
use App\Models\Core\Notifications\NotificationTemplate;
use App\Models\User;
use Illuminate\Support\Collection;

class NotificationService
{
    /**
     * @param  iterable<User>  $recipients
     */
    public function notifyMany(
        iterable $recipients,
        string $type,
        array $context = [],
        ?User $actor = null,
        ?string $moduleName = null,
        ?int $referenceId = null,
        ?string $actionUrl = null,
        array $data = [],
    ): Collection {
        return collect($recipients)
            ->filter(fn ($recipient) => $recipient instanceof User)
            ->unique('id')
            ->map(fn (User $recipient) => $this->notify($recipient, $type, $context, $actor, $moduleName, $referenceId, $actionUrl, $data))
            ->values();
    }

    public function notify(
        User $recipient,
        string $type,
        array $context = [],
        ?User $actor = null,
        ?string $moduleName = null,
        ?int $referenceId = null,
        ?string $actionUrl = null,
        array $data = [],
        ?string $idempotencyKey = null,
    ): CoreNotification {
        $template = NotificationTemplate::query()->where('type', $type)->where('is_active', true)->first();

        $title = $this->render($template?->title_template ?? $context['title'] ?? $type, $context);
        $message = $this->render($template?->message_template ?? $context['message'] ?? null, $context);

        $attributes = [
            'recipient_id' => $recipient->id,
            'actor_id' => $actor?->id,
            'type' => $type,
            'title' => $title,
            'message' => $message,
            'module_name' => $moduleName,
            'reference_id' => $referenceId,
            'action_url' => $actionUrl,
            'data' => $data + ['context' => $context, 'channels' => $template?->channels ?? ['in_app']],
            'idempotency_key' => $idempotencyKey,
        ];

        return $idempotencyKey === null
            ? CoreNotification::create($attributes)
            : CoreNotification::query()->createOrFirst(['idempotency_key' => $idempotencyKey], $attributes);
    }

    public function markRead(CoreNotification $notification): void
    {
        if (! $notification->read_at) {
            $notification->update(['read_at' => now()]);
        }
    }

    public function markUnread(CoreNotification $notification): void
    {
        $notification->update(['read_at' => null]);
    }

    public function markAllRead(User $recipient): int
    {
        return CoreNotification::query()
            ->where('recipient_id', $recipient->id)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);
    }

    private function render(?string $template, array $context): ?string
    {
        if ($template === null) {
            return null;
        }

        foreach ($context as $key => $value) {
            if (is_scalar($value) || $value === null) {
                $template = str_replace('{{'.$key.'}}', (string) $value, $template);
            }
        }

        return $template;
    }
}
