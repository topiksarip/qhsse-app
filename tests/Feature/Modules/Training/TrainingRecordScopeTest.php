<?php

namespace Tests\Feature\Modules\Training;

use App\Models\Core\MasterData\Site;
use App\Models\Core\Users\Employee;
use App\Models\Modules\Training\TrainingRecord;
use App\Models\User;
use App\Modules\Training\TrainingAccess;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

uses(RefreshDatabase::class);

beforeEach(function () {
    Artisan::call('db:seed', ['--class' => 'RolesAndPermissionsSeeder']);
});

test('M7 WS-1: core.scope.all returns every record regardless of role', function (): void {
    $empA = Employee::factory()->create();
    $empB = Employee::factory()->create();
    TrainingRecord::factory()->create(['employee_id' => $empA->id]);
    TrainingRecord::factory()->create(['employee_id' => $empB->id]);

    $user = User::factory()->create();
    $user->givePermissionTo('core.scope.all');

    $count = (new TrainingAccess())->scope(TrainingRecord::query(), $user)->count();
    expect($count)->toBe(2);
});

test('M7 WS-1: core.scope.site narrows to employee site', function (): void {
    $site = Site::factory()->create();
    $other = Site::factory()->create();
    $empSame = Employee::factory()->create(['site_id' => $site->id]);
    $empOther = Employee::factory()->create(['site_id' => $other->id]);
    TrainingRecord::factory()->create(['employee_id' => $empSame->id]);
    TrainingRecord::factory()->create(['employee_id' => $empOther->id]);

    $user = User::factory()->create();
    $me = Employee::factory()->create(['site_id' => $site->id]);
    $user->update(['employee_id' => $me->id]);
    $user->givePermissionTo('core.scope.site');

    $count = (new TrainingAccess())->scope(TrainingRecord::query(), $user)->count();
    expect($count)->toBe(1);
});

test('M7 WS-1: core.scope.own narrows to own record', function (): void {
    $emp = Employee::factory()->create();
    $other = Employee::factory()->create();
    TrainingRecord::factory()->create(['employee_id' => $emp->id]);
    TrainingRecord::factory()->create(['employee_id' => $other->id]);

    $user = User::factory()->create();
    $user->update(['employee_id' => $emp->id]);
    $user->givePermissionTo('core.scope.own');

    $count = (new TrainingAccess())->scope(TrainingRecord::query(), $user)->count();
    expect($count)->toBe(1);
});

test('M7 WS-1: no scope permission fails closed (zero rows)', function (): void {
    TrainingRecord::factory()->create();
    $user = User::factory()->create();

    $count = (new TrainingAccess())->scope(TrainingRecord::query(), $user)->count();
    expect($count)->toBe(0);
});

test('M7 WS-1: core.scope.department narrows to employee department', function (): void {
    $dept = \App\Models\Core\MasterData\Department::factory()->create();
    $otherDept = \App\Models\Core\MasterData\Department::factory()->create();
    $empSame = Employee::factory()->create(['department_id' => $dept->id]);
    $empOther = Employee::factory()->create(['department_id' => $otherDept->id]);
    TrainingRecord::factory()->create(['employee_id' => $empSame->id]);
    TrainingRecord::factory()->create(['employee_id' => $empOther->id]);

    $user = User::factory()->create();
    $me = Employee::factory()->create(['department_id' => $dept->id]);
    $user->update(['employee_id' => $me->id]);
    $user->givePermissionTo('core.scope.department');

    $count = (new TrainingAccess())->scope(TrainingRecord::query(), $user)->count();
    expect($count)->toBe(1);
});
