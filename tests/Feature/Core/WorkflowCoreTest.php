<?php

use App\Core\Workflow\WorkflowService;
use App\Models\Core\Workflow\WorkflowDefinition;
use App\Models\Core\Workflow\WorkflowHistory;
use App\Models\Core\Workflow\WorkflowInstance;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Database\Seeders\WorkflowSeeder;
use Illuminate\Validation\UnauthorizedException;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
});

function workflowAdmin(): User
{
    $user = User::factory()->create();
    $user->assignRole('Super Admin');

    return $user;
}

it('seeds baseline workflow definitions and transitions', function () {
    $this->seed(WorkflowSeeder::class);

    expect(WorkflowDefinition::where('module_name', 'incident')->exists())->toBeTrue()
        ->and(WorkflowDefinition::where('module_name', 'capa')->exists())->toBeTrue()
        ->and(WorkflowDefinition::where('module_name', 'document')->exists())->toBeTrue()
        ->and(WorkflowDefinition::where('module_name', 'incident')->first()->transitions()->count())->toBeGreaterThan(0);
});

it('starts workflow instances and records history', function () {
    $this->seed(WorkflowSeeder::class);
    $admin = workflowAdmin();

    $instance = app(WorkflowService::class)->start('incident', 1001, $admin);

    expect($instance->current_status)->toBe('draft')
        ->and(WorkflowHistory::where('workflow_instance_id', $instance->id)->where('action_key', 'start')->exists())->toBeTrue();
});

it('performs valid transitions and records actor history', function () {
    $this->seed(WorkflowSeeder::class);
    $admin = workflowAdmin();
    $service = app(WorkflowService::class);

    $service->start('incident', 1002, $admin);
    $instance = $service->transition('incident', 1002, 'submit', $admin);

    expect($instance->current_status)->toBe('submitted')
        ->and(WorkflowHistory::where('module_name', 'incident')->where('reference_id', 1002)->where('action_key', 'submit')->where('actor_id', $admin->id)->exists())->toBeTrue();
});

it('rejects invalid transitions', function () {
    $this->seed(WorkflowSeeder::class);
    $admin = workflowAdmin();
    $service = app(WorkflowService::class);

    $service->start('incident', 1003, $admin);
    $service->transition('incident', 1003, 'close', $admin, 'invalid close');
})->throws(RuntimeException::class);

it('requires reason when configured', function () {
    $this->seed(WorkflowSeeder::class);
    $admin = workflowAdmin();
    $service = app(WorkflowService::class);

    $service->start('incident', 1004, $admin);
    $service->transition('incident', 1004, 'submit', $admin);
    $service->transition('incident', 1004, 'reject', $admin);
})->throws(RuntimeException::class);

it('blocks transitions when actor lacks transition permission', function () {
    $this->seed(WorkflowSeeder::class);
    $admin = workflowAdmin();
    $plainUser = User::factory()->create();
    $service = app(WorkflowService::class);

    $service->start('incident', 1005, $admin);
    $service->transition('incident', 1005, 'submit', $plainUser);
})->throws(UnauthorizedException::class);

it('allows authorized users to run workflow from UI route', function () {
    $this->seed(WorkflowSeeder::class);
    $admin = workflowAdmin();

    $this->actingAs($admin)->post(route('core.workflow.run'), [
        'module_name' => 'incident',
        'reference_id' => 1006,
    ])->assertRedirect(route('core.workflow.index'));

    $this->actingAs($admin)->post(route('core.workflow.run'), [
        'module_name' => 'incident',
        'reference_id' => 1006,
        'action_key' => 'submit',
    ])->assertRedirect(route('core.workflow.index'));

    $this->assertDatabaseHas('workflow_instances', ['module_name' => 'incident', 'reference_id' => 1006, 'current_status' => 'submitted']);
});

it('blocks workflow access without permission', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->get(route('core.workflow.index'))->assertForbidden();
});
