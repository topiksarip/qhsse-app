<?php

use App\Core\Activity\ActivityService;
use App\Core\Workflow\WorkflowService;
use App\Models\Core\Activity\ActivityLog;
use App\Models\Core\Comments\Comment;
use App\Models\Modules\Incident\IncidentReport;
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
    $incident = IncidentReport::factory()->create();

    $this->actingAs($admin)->post(route('core.comments.store'), [
        'module_name' => 'incident',
        'reference_id' => $incident->id,
        'body' => 'Please check this @supervisor and @hse-officer',
        'is_internal' => true,
    ])->assertRedirect(route('core.comments-activity.index', ['module_name' => 'incident', 'reference_id' => $incident->id]));

    $comment = Comment::firstOrFail();

    expect($comment->module_name)->toBe('incident')
        ->and($comment->reference_id)->toBe($incident->id)
        ->and($comment->mentions)->toBe(['supervisor', 'hse-officer'])
        ->and($comment->is_internal)->toBeTrue();

    $this->assertDatabaseHas('activity_logs', [
        'module_name' => 'incident',
        'reference_id' => $incident->id,
        'event' => 'comment.created',
        'actor_id' => $admin->id,
    ]);
});

it('shows comments and activity for a module reference', function () {
    $admin = commentsAdmin();
    $incident = IncidentReport::factory()->create();

    app(ActivityService::class)->log('incident', $incident->id, 'test.activity', 'Test activity', $admin);
    Comment::create([
        'module_name' => 'incident',
        'reference_id' => $incident->id,
        'author_id' => $admin->id,
        'body' => 'Visible comment',
    ]);

    $this->actingAs($admin)->get(route('core.comments-activity.index', ['module_name' => 'incident', 'reference_id' => $incident->id]))->assertOk();
});

it('records workflow status events in the activity timeline', function () {
    $this->seed(WorkflowSeeder::class);
    $admin = commentsAdmin();
    $service = app(WorkflowService::class);
    $incident = IncidentReport::factory()->create();

    $service->start('incident', $incident->id, $admin);
    $service->transition('incident', $incident->id, 'submit', $admin);

    $this->assertDatabaseHas('activity_logs', [
        'module_name' => 'incident',
        'reference_id' => $incident->id,
        'event' => 'workflow.transitioned',
        'actor_id' => $admin->id,
    ]);
});

it('marks comments deleted and records delete activity', function () {
    $admin = commentsAdmin();
    $incident = IncidentReport::factory()->create();

    $this->actingAs($admin)->post(route('core.comments.store'), [
        'module_name' => 'incident',
        'reference_id' => $incident->id,
        'body' => 'Delete me',
    ]);

    $comment = Comment::firstOrFail();

    $this->actingAs($admin)->delete(route('core.comments.destroy', $comment))
        ->assertRedirect(route('core.comments-activity.index', ['module_name' => 'incident', 'reference_id' => $incident->id]));

    $comment->refresh();

    expect($comment->deleted_at)->not->toBeNull()
        ->and($comment->deleted_by)->toBe($admin->id);

    $this->assertDatabaseHas('activity_logs', [
        'module_name' => 'incident',
        'reference_id' => $incident->id,
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
