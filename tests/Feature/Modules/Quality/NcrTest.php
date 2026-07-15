<?php

namespace Tests\Feature\Modules\Quality;

use App\Models\Core\MasterData\Site;
use App\Models\User;
use App\Models\Modules\Quality\Ncr;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use function Pest\Laravel\actingAs;
use Tests\TestCase;

uses(RefreshDatabase::class);

beforeEach(function () {
    Artisan::call('db:seed', ['--class' => 'RolesAndPermissionsSeeder']);
    Artisan::call('db:seed', ['--class' => 'WorkflowSeeder']);
    Artisan::call('db:seed', ['--class' => 'NumberingFormatSeeder']);
});

test('QHSSE Manager can create NCR and workflow starts', function (): void {
    $user = User::factory()->create();
    $user->assignRole('QHSSE Manager');
    $user->givePermissionTo(['quality.ncrs.view', 'quality.ncrs.create', 'quality.ncrs.update', 'quality.ncrs.close']);
    actingAs($user);

    $site = Site::factory()->create();
    $severity = \App\Models\Core\MasterData\Severity::factory()->create();

    $response = $this->post(route('quality.ncrs.store'), [
        'title' => 'Test NCR',
        'source' => 'internal',
        'description' => 'desc',
        'site_id' => $site->id,
        'severity_id' => $severity->id,
    ]);

    $response->assertRedirect();
    $ncr = Ncr::latest()->first();
    expect($ncr->status)->toBe('open');
});

test('NCR lifecycle open->submit->review->close gated by RCA', function (): void {
    $user = User::factory()->create();
    $user->assignRole('QHSSE Manager');
    $user->givePermissionTo(['quality.ncrs.view', 'quality.ncrs.update', 'quality.ncrs.close']);
    actingAs($user);

    $ncr = Ncr::factory()->create(['status' => 'open']);

    $this->post(route('quality.ncrs.transition', ['ncr' => $ncr, 'action' => 'submit']))
        ->assertRedirect();
    expect($ncr->fresh()->status)->toBe('under_review');

    $this->post(route('quality.ncrs.transition', ['ncr' => $ncr, 'action' => 'review']))
        ->assertRedirect();
    expect($ncr->fresh()->status)->toBe('in_progress');

    // close without RCA -> 422
    $this->post(route('quality.ncrs.transition', ['ncr' => $ncr, 'action' => 'close']), ['reason' => str_repeat('x', 15)])
        ->assertStatus(422);

    $ncr->update([
        'root_cause' => 'root cause text here',
        'corrective_action' => 'corrective action text',
        'preventive_action' => 'preventive action text',
    ]);

    $this->post(route('quality.ncrs.transition', ['ncr' => $ncr, 'action' => 'close']), ['reason' => str_repeat('x', 15)])
        ->assertRedirect();
    expect($ncr->fresh()->status)->toBe('closed');
    expect($ncr->fresh()->closed_at)->not->toBeNull();
});

test('closed NCR cannot be updated (WS-2 guard)', function (): void {
    $user = User::factory()->create();
    $user->assignRole('QHSSE Manager');
    $user->givePermissionTo(['quality.ncrs.view', 'quality.ncrs.update']);
    actingAs($user);

    $ncr = Ncr::factory()->create(['status' => 'closed']);

    $this->put(route('quality.ncrs.update', $ncr), ['title' => 'Changed'])
        ->assertStatus(403);
});

test('QHSSE Officer in other site cannot view NCR (WS-3 scope)', function (): void {
    $otherSite = Site::factory()->create();
    $ncr = Ncr::factory()->create(['site_id' => $otherSite->id]);

    $officer = User::factory()->create();
    $officer->assignRole('QHSSE Officer');
    $employee = \App\Models\Core\Users\Employee::factory()->create();
    $officer->update(['employee_id' => $employee->id]);
    $officer->givePermissionTo(['quality.ncrs.view', 'core.scope.site']);

    actingAs($officer);
    $this->get(route('quality.ncrs.show', $ncr))->assertStatus(403);
});
