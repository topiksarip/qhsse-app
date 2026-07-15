<?php

namespace Tests\Feature\Modules\Capa;

use App\Models\Modules\Capa\CapaAction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use function Pest\Laravel\actingAs;
use Tests\TestCase;

uses(RefreshDatabase::class);

beforeEach(function () {
    Artisan::call('db:seed', ['--class' => 'RolesAndPermissionsSeeder']);
    Artisan::call('db:seed', ['--class' => 'QhsseMasterDataSeeder']);
    Artisan::call('db:seed', ['--class' => 'WorkflowSeeder']);
});

/**
 * Drive a CAPA action through the workflow to a given status.
 * The WorkflowInstance is created lazily by WorkflowService on first transition,
 * so we must step through transitions rather than assume a status.
 */
function driveTo(User $actor, CapaAction $action, string $target): void
{
    $steps = [
        'open' => [],
        'in_progress' => ['start'],
        'waiting_verification' => ['start', 'submit_verification'],
    ];

    foreach ($steps[$target] ?? [] as $step) {
        $route = match ($step) {
            'start' => 'capa.actions.start',
            'submit_verification' => 'capa.actions.submit_verification',
        };
        actingAs($actor);
        test()->post(route($route, $action))->assertRedirect();
    }
}

test('CORE WS-2: QHSSE Manager can run the full CAPA workflow without 403', function (): void {
    $manager = User::factory()->create();
    $manager->assignRole('QHSSE Manager');

    $action = CapaAction::factory()->create(['status' => 'open']);

    actingAs($manager);
    $this->post(route('capa.actions.start', $action))->assertRedirect();
    expect($action->fresh()->status)->toBe('in_progress');

    $this->post(route('capa.actions.submit_verification', $action))->assertRedirect();
    expect($action->fresh()->status)->toBe('waiting_verification');

    $this->post(route('capa.actions.verify_close', $action), ['reason' => 'Verified and effective'])
        ->assertRedirect();
    expect($action->fresh()->status)->toBe('closed');
});

test('CORE WS-2: QHSSE Officer can verify-close a CAPA (core.workflow.transition granted)', function (): void {
    $officer = User::factory()->create();
    $officer->assignRole('QHSSE Officer');
    $officer->givePermissionTo('core.scope.all'); // satisfy canAccess; scope is not under test here

    $action = CapaAction::factory()->create(['status' => 'open']);
    driveTo($officer, $action, 'waiting_verification');

    actingAs($officer);
    $this->post(route('capa.actions.verify_close', $action), ['reason' => 'Reviewed'])
        ->assertRedirect();

    expect($action->fresh()->status)->toBe('closed');
});

test('CORE WS-2: reject transition works for QHSSE Manager', function (): void {
    $manager = User::factory()->create();
    $manager->assignRole('QHSSE Manager');

    $action = CapaAction::factory()->create(['status' => 'open']);
    driveTo($manager, $action, 'waiting_verification');

    actingAs($manager);
    $this->post(route('capa.actions.reject', $action), ['reason' => 'Not effective'])
        ->assertRedirect();

    expect($action->fresh()->status)->toBe('rejected');
});

test('CORE WS-2: regression - role without core.workflow.transition cannot transition CAPA', function (): void {
    $user = User::factory()->create();
    // Route-level close + scope satisfied, but NO core.workflow.transition:
    $user->givePermissionTo(['capa.actions.close', 'core.scope.all']);

    $action = CapaAction::factory()->create(['status' => 'open']);

    actingAs($user);
    // verify_close requires reason + the core.workflow.transition permission internally.
    $this->post(route('capa.actions.verify_close', $action), ['reason' => 'x'])
        ->assertRedirect(); // returns back with workflow error, not a hard 403

    // Status must remain unchanged because the workflow permission check failed
    expect($action->fresh()->status)->toBe('open');
});
