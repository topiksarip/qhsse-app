<?php

namespace Tests\Feature\Modules\Apd;

use App\Models\Modules\Apd\ApdCatalog;
use App\Models\Modules\Apd\ApdItem;
use App\Models\Modules\Apd\ApdIssuance;
use App\Models\Core\MasterData\Site;
use App\Models\Core\MasterData\Department;
use App\Models\User;
use Database\Seeders\NumberingFormatSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Database\Seeders\WorkflowSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use PHPUnit\Framework\Attributes\UsesRefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed([RolesAndPermissionsSeeder::class, NumberingFormatSeeder::class, WorkflowSeeder::class]);
});

function apdActor(string $role, Site $site, Department $department): User
{
    $employee = \App\Models\Core\Users\Employee::factory()->create([
        'site_id' => $site->id,
        'department_id' => $department->id,
    ]);
    $user = User::factory()->linkedToEmployee($employee)->create();
    $user->assignRole($role);

    return $user;
}

function apdStockItem(User $actor, Site $site, Department $department): ApdItem
{
    static $seq = 0;
    $seq++;
    $catalog = ApdCatalog::create([
        'catalog_code' => sprintf('PPE-IS-%04d', $seq),
        'name' => "APD {$seq}",
        'category' => 'head_protection',
        'track_type' => 'serial',
        'site_id' => $site->id,
        'department_id' => $department->id,
        'min_stock' => 0,
        'reorder_point' => 0,
        'is_active' => true,
        'created_by' => $actor->id,
        'updated_by' => $actor->id,
    ]);

    return ApdItem::create([
        'item_number' => sprintf('PPE-IT-%04d', $seq),
        'catalog_id' => $catalog->id,
        'site_id' => $site->id,
        'department_id' => $department->id,
        'track_type' => 'serial',
        'status' => 'in_stock',
        'quantity' => 1,
        'created_by' => $actor->id,
        'updated_by' => $actor->id,
    ]);
}

it('registers issuance routes', function () {
    expect(Route::has('apd.issuances.index'))->toBeTrue()
        ->and(Route::has('apd.issuances.create'))->toBeTrue()
        ->and(Route::has('apd.issuances.store'))->toBeTrue()
        ->and(Route::has('apd.issuances.show'))->toBeTrue()
        ->and(Route::has('apd.issuances.request'))->toBeTrue()
        ->and(Route::has('apd.issuances.approve'))->toBeTrue()
        ->and(Route::has('apd.issuances.issue'))->toBeTrue()
        ->and(Route::has('apd.issuances.process'))->toBeTrue();
});

it('allows QHSSE to issue directly and removes item from stock', function () {
    $site = Site::factory()->create();
    $dept = Department::factory()->for($site)->create();
    $manager = apdActor('QHSSE Manager', $site, $dept);
    $target = apdActor('Employee / Reporter', $site, $dept);
    $item = apdStockItem($manager, $site, $dept);

    $this->actingAs($manager)
        ->post(route('apd.issuances.store'), [
            'apd_item_id' => $item->id,
            'holder_type' => 'employee',
            'holder_id' => $target->employee->id,
            'quantity' => 1,
            'condition_out' => 'good',
        ])
        ->assertRedirect(route('apd.issuances.show', ApdIssuance::latest()->first()));

    $issuance = ApdIssuance::latest()->first();
    expect($issuance->status)->toBe('issued')
        ->and($issuance->issue_number)->toStartWith('PPE-ISSUE-'.date('Y').'-');

    $item->refresh();
    expect($item->status)->toBe('issued')
        ->and($item->holder_type)->toBe('employee')
        ->and($item->holder_id)->toBe($target->employee->id);
});

it('runs employee request -> supervisor approve -> QHSSE issue', function () {
    $site = Site::factory()->create();
    $dept = Department::factory()->for($site)->create();
    $manager = apdActor('QHSSE Manager', $site, $dept);
    $supervisor = apdActor('Supervisor', $site, $dept);
    $employee = apdActor('Employee / Reporter', $site, $dept);
    $item = apdStockItem($manager, $site, $dept);

    $this->actingAs($employee)
        ->post(route('apd.issuances.store'), [
            'apd_item_id' => $item->id,
            'holder_type' => 'employee',
            'holder_id' => $employee->employee->id,
            'quantity' => 1,
            'start_as_request' => true,
        ])
        ->assertRedirect();

    $issuance = ApdIssuance::latest()->first();
    expect($issuance->status)->toBe('requested');
    $item->refresh();
    expect($item->status)->toBe('in_stock');

    $this->actingAs($supervisor)->post(route('apd.issuances.approve', $issuance))->assertRedirect();
    $issuance->refresh();
    expect($issuance->status)->toBe('approved');

    $this->actingAs($manager)->post(route('apd.issuances.issue', $issuance))->assertRedirect();
    $issuance->refresh();
    expect($issuance->status)->toBe('issued');
    $item->refresh();
    expect($item->status)->toBe('issued');
});

it('restores stock on return', function () {
    $site = Site::factory()->create();
    $dept = Department::factory()->for($site)->create();
    $manager = apdActor('QHSSE Manager', $site, $dept);
    $employee = apdActor('Employee / Reporter', $site, $dept);
    $item = apdStockItem($manager, $site, $dept);

    // Issue properly through the lifecycle so the workflow state is initialized.
    $issuance = app(\App\Modules\Apd\ApdLifecycle::class)->create([
        'apd_item_id' => $item->id,
        'holder_type' => 'employee',
        'holder_id' => $employee->employee->id,
        'quantity' => 1,
        'condition_out' => 'good',
    ], $manager);

    $this->actingAs($manager)
        ->post(route('apd.issuances.process', $issuance), ['action' => 'return', 'condition_in' => 'good'])
        ->assertRedirect();

    $issuance->refresh();
    expect($issuance->status)->toBe('returned');
    $item->refresh();
    expect($item->status)->toBe('in_stock')->and($item->holder_type)->toBeNull();
});

it('blocks scoped user from other site issuance', function () {
    $siteA = Site::factory()->create();
    $siteB = Site::factory()->create();
    $deptA = Department::factory()->for($siteA)->create();
    $deptB = Department::factory()->for($siteB)->create();
    $managerA = apdActor('QHSSE Manager', $siteA, $deptA);
    $viewerB = apdActor('Employee / Reporter', $siteB, $deptB);
    $itemA = apdStockItem($managerA, $siteA, $deptA);

    $issuance = ApdIssuance::create([
        'issue_number' => 'PPE-ISSUE-'.date('Y').'-0008',
        'apd_item_id' => $itemA->id,
        'quantity' => 1,
        'holder_type' => 'employee',
        'holder_id' => $managerA->employee->id,
        'status' => 'issued',
        'created_by' => $managerA->id,
        'updated_by' => $managerA->id,
    ]);

    $this->actingAs($viewerB)->get(route('apd.issuances.show', $issuance))->assertForbidden();
});

it('blocks employee from issuing directly (creates draft only)', function () {
    $site = Site::factory()->create();
    $dept = Department::factory()->for($site)->create();
    $manager = apdActor('QHSSE Manager', $site, $dept);
    $employee = apdActor('Employee / Reporter', $site, $dept);
    $item = apdStockItem($manager, $site, $dept);

    $this->actingAs($employee)
        ->post(route('apd.issuances.store'), [
            'apd_item_id' => $item->id,
            'holder_type' => 'employee',
            'holder_id' => $employee->employee->id,
            'quantity' => 1,
            'start_as_request' => false,
        ])
        ->assertRedirect();

    // Employee may create a draft issuance but it is NOT issued and stock is untouched.
    $issuance = ApdIssuance::latest()->first();
    expect($issuance->status)->toBe('draft');
    $item->refresh();
    expect($item->status)->toBe('in_stock');
});

it('rejects serial item with quantity over 1', function () {
    $site = Site::factory()->create();
    $dept = Department::factory()->for($site)->create();
    $manager = apdActor('QHSSE Manager', $site, $dept);
    $employee = apdActor('Employee / Reporter', $site, $dept);
    $item = apdStockItem($manager, $site, $dept);

    $this->actingAs($manager)
        ->post(route('apd.issuances.store'), [
            'apd_item_id' => $item->id,
            'holder_type' => 'employee',
            'holder_id' => $employee->employee->id,
            'quantity' => 5,
        ])
        ->assertSessionHasErrors('quantity');

    expect(ApdIssuance::query()->count())->toBe(0);
});
