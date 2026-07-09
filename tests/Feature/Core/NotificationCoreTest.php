<?php

use App\Core\Notifications\NotificationService;
use App\Models\Core\Notifications\CoreNotification;
use App\Models\Core\Notifications\NotificationTemplate;
use App\Models\User;
use Database\Seeders\NotificationTemplateSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;

beforeEach(function () {
    $this->withoutVite();
    $this->seed(RolesAndPermissionsSeeder::class);
});

function notificationAdmin(): User
{
    $user = User::factory()->create();
    $user->assignRole('Super Admin');

    return $user;
}

it('seeds notification templates', function () {
    $this->seed(NotificationTemplateSeeder::class);

    expect(NotificationTemplate::where('type', 'core.test')->exists())->toBeTrue()
        ->and(NotificationTemplate::where('type', 'comment.mentioned')->exists())->toBeTrue();
});

it('creates in app notifications from templates', function () {
    $this->seed(NotificationTemplateSeeder::class);
    $recipient = User::factory()->create();
    $actor = notificationAdmin();

    $notification = app(NotificationService::class)->notify(
        $recipient,
        'core.test',
        ['module_name' => 'incident', 'reference_id' => 4001],
        $actor,
        'incident',
        4001,
    );

    expect($notification->recipient_id)->toBe($recipient->id)
        ->and($notification->title)->toBe('Test notification for incident')
        ->and($notification->message)->toBe('Reference #4001 generated a test notification.')
        ->and($notification->read_at)->toBeNull();
});

it('marks notifications read unread and all read', function () {
    $recipient = User::factory()->create();
    $service = app(NotificationService::class);

    $first = $service->notify($recipient, 'manual', ['title' => 'First']);
    $second = $service->notify($recipient, 'manual', ['title' => 'Second']);

    $service->markRead($first);
    expect($first->refresh()->read_at)->not->toBeNull();

    $service->markUnread($first);
    expect($first->refresh()->read_at)->toBeNull();

    $service->markAllRead($recipient);
    expect(CoreNotification::where('recipient_id', $recipient->id)->whereNull('read_at')->count())->toBe(0);
});

it('allows users to view only their notifications and manage read state', function () {
    $recipient = notificationAdmin();
    $other = notificationAdmin();
    $service = app(NotificationService::class);

    $mine = $service->notify($recipient, 'manual', ['title' => 'Mine']);
    $others = $service->notify($other, 'manual', ['title' => 'Other']);

    $this->actingAs($recipient)->get(route('core.notifications.index'))->assertOk();
    $this->actingAs($recipient)->patch(route('core.notifications.read', $mine))->assertRedirect();
    $this->actingAs($recipient)->patch(route('core.notifications.read', $others))->assertForbidden();
});

it('sends test notifications from UI route and blocks unauthorized access', function () {
    $this->seed(NotificationTemplateSeeder::class);
    $admin = notificationAdmin();
    $recipient = User::factory()->create();
    $plainUser = User::factory()->create();

    $this->actingAs($admin)->post(route('core.notifications.test'), [
        'recipient_id' => $recipient->id,
        'module_name' => 'incident',
        'reference_id' => 4002,
    ])->assertRedirect(route('core.notifications.index'));

    $this->assertDatabaseHas('core_notifications', [
        'recipient_id' => $recipient->id,
        'type' => 'core.test',
        'module_name' => 'incident',
        'reference_id' => 4002,
    ]);

    $this->actingAs($plainUser)->get(route('core.notifications.index'))->assertForbidden();
});
