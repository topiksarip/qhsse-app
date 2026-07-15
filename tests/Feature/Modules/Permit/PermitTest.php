<?php

namespace Tests\Feature\Modules\Permit;

use App\Models\Core\MasterData\Site;
use App\Models\Modules\Permit\Permit;
use App\Models\Modules\Permit\PermitChecklist;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use function Pest\Laravel\actingAs;
use Tests\TestCase;

uses(RefreshDatabase::class);

beforeEach(function () {
    Artisan::call('db:seed', ['--class' => 'RolesAndPermissionsSeeder']);
    Artisan::call('db:seed', ['--class' => 'WorkflowSeeder']);
});

function permitUser(array $perms = []): User
{
    $user = User::factory()->create();
    $user->assignRole('QHSSE Manager');
    $user->givePermissionTo(array_merge([
        'permit.work.view', 'permit.work.create', 'permit.work.update',
        'permit.work.approve', 'permit.work.close', 'permit.work.cancel',
    ], $perms));
    return $user;
}

test('M8 WS-1: cannot approve own permit (COI)', function (): void {
    $creator = permitUser();
    $permit = Permit::factory()->create([
        'status' => 'under_review',
        'created_by' => $creator->id,
        'site_id' => Site::factory(),
    ]);

    actingAs($creator);
    $this->post(route('permit.work.transition', $permit), ['action' => 'approve'])
        ->assertStatus(422);
});

test('M8 WS-1: cannot activate with unsigned checklist', function (): void {
    $user = permitUser();
    $permit = Permit::factory()->create([
        'status' => 'approved',
        'created_by' => User::factory()->create()->id,
        'site_id' => Site::factory(),
    ]);
    PermitChecklist::create([
        'permit_id' => $permit->id,
        'item_text' => 'Isolasi energi',
        'is_checked' => false,
    ]);

    actingAs($user);
    $this->post(route('permit.work.transition', $permit), ['action' => 'activate'])
        ->assertStatus(422);
});

test('M8 WS-1: close/reject requires reason >= 10 chars', function (): void {
    $user = permitUser();
    $permit = Permit::factory()->create([
        'status' => 'active',
        'created_by' => User::factory()->create()->id,
        'site_id' => Site::factory(),
    ]);

    actingAs($user);
    $this->post(route('permit.work.transition', $permit), ['action' => 'close', 'reason' => 'short'])
        ->assertSessionHasErrors('reason');

    $this->post(route('permit.work.transition', $permit), ['action' => 'close', 'reason' => str_repeat('x', 12)])
        ->assertRedirect();
    expect($permit->fresh()->status)->toBe('closed');
});

test('M8 WS-1: full lifecycle runs with correct status progression', function (): void {
    $approver = permitUser();
    $creator = User::factory()->create();
    $creator->assignRole('QHSSE Officer');
    $creator->givePermissionTo(['permit.work.view', 'permit.work.create', 'permit.work.update']);

    $permit = Permit::factory()->create([
        'status' => 'draft',
        'created_by' => $creator->id,
        'site_id' => Site::factory(),
    ]);

    // submit
    actingAs($creator);
    $this->post(route('permit.work.transition', $permit), ['action' => 'submit'])->assertRedirect();
    expect($permit->fresh()->status)->toBe('submitted');

    // review (by approver, different user)
    actingAs($approver);
    $this->post(route('permit.work.transition', $permit), ['action' => 'review'])->assertRedirect();
    expect($permit->fresh()->status)->toBe('under_review');

    $this->post(route('permit.work.transition', $permit), ['action' => 'approve', 'reason' => str_repeat('x', 12)])->assertRedirect();
    expect($permit->fresh()->status)->toBe('approved');

    // add signed checklist, then activate
    PermitChecklist::create(['permit_id' => $permit->id, 'item_text' => 'Gas test', 'is_checked' => true]);
    $this->post(route('permit.work.transition', $permit), ['action' => 'activate', 'reason' => str_repeat('x', 12)])->assertRedirect();
    expect($permit->fresh()->status)->toBe('active');
});
