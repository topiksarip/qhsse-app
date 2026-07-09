<?php

use App\Core\Activity\ActivityService;
use App\Core\Workflow\WorkflowService;
use App\Models\Core\Activity\ActivityLog;
use App\Models\Core\Comments\Comment;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Database\Seeders\WorkflowSeeder;

beforeEach(function () {
    $this->withoutVite();
    $this->seed(RolesAndPermissionsSeeder::class);
});

function commentsAdmin(): User
{
    $user = User::factory()->create();
    $user->assignRole('Super Admin');

    return $user;
}

it('adds comments to module references and extracts mentions', function () {
    $admin = commentsAdmin();

    $this->actingAs($admin)->post(route('core.comments.store'), [
        'module_name' => 'incident',
        'reference_id' => 3001,
        'body' => 'Please check this @supervisor and @hse-officer',
        'is_internal' => true,
    ])->assertRedirect(route('core.comments-activity.index', ['module_name' => 'incident', 'reference_id' => 3001]));

    $comment = Comment::firstOrFail();

    expect($comment->module_name)->toBe('incident')
        ->and($comment->reference_id)->toBe(3001)
        ->and($comment->mentions)->toBe(['supervisor', 'hse-officer'])
        ->and($comment->is_internal)->toBeTrue();

    $this->assertDatabaseHas('activity_logs', [
        'module_name' => 'incident',
        'reference_id' => 3001,
        'event' => 'comment.created',
        'actor_id' => $admin->id,
    ]);
});

it('shows comments and activity for a module reference', function () {
    $admin = commentsAdmin();

    app(ActivityService::class)->log('incident', 3002, 'test.activity', 'Test activity', $admin);
    Comment::create([
        'module_name' => 'incident',
        'reference_id' => 3002,
        'author_id' => $admin->id,
        'body' => 'Visible comment',
    ]);

    $this->actingAs($admin)->get(route('core.comments-activity.index', ['module_name' => 'incident', 'reference_id' => 3002]))->assertOk();
});

it('records workflow status events in the activity timeline', function () {
    $this->seed(WorkflowSeeder::class);
    $admin = commentsAdmin();
    $service = app(WorkflowService::class);

    $service->start('incident', 3003, $admin);
    $service->transition('incident', 3003, 'submit', $admin);

    $this->assertDatabaseHas('activity_logs', [
        'module_name' => 'incident',
        'reference_id' => 3003,
        'event' => 'workflow.transitioned',
        'actor_id' => $admin->id,
    ]);
});

it('marks comments deleted and records delete activity', function () {
    $admin = commentsAdmin();

    $this->actingAs($admin)->post(route('core.comments.store'), [
        'module_name' => 'incident',
        'reference_id' => 3004,
        'body' => 'Delete me',
    ]);

    $comment = Comment::firstOrFail();

    $this->actingAs($admin)->delete(route('core.comments.destroy', $comment))
        ->assertRedirect(route('core.comments-activity.index', ['module_name' => 'incident', 'reference_id' => 3004]));

    $comment->refresh();

    expect($comment->deleted_at)->not->toBeNull()
        ->and($comment->deleted_by)->toBe($admin->id);

    $this->assertDatabaseHas('activity_logs', [
        'module_name' => 'incident',
        'reference_id' => 3004,
        'event' => 'comment.deleted',
    ]);
});

it('blocks comments and activity access without permission', function () {
    $plainUser = User::factory()->create();

    $this->actingAs($plainUser)->get(route('core.comments-activity.index'))->assertForbidden();
    $this->actingAs($plainUser)->post(route('core.comments.store'), [
        'module_name' => 'incident',
        'reference_id' => 3005,
        'body' => 'No access',
    ])->assertForbidden();
});
