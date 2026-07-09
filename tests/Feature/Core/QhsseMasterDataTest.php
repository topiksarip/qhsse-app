<?php

use App\Models\Core\MasterData\Priority;
use App\Models\Core\MasterData\RiskMatrixLevel;
use App\Models\Core\MasterData\Severity;
use App\Models\Core\MasterData\Status;
use App\Models\User;
use Database\Seeders\QhsseMasterDataSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
});

function qhsseMasterAdmin(): User
{
    $user = User::factory()->create();
    $user->assignRole('Super Admin');

    return $user;
}

it('seeds baseline qhsse master data', function () {
    $this->seed(QhsseMasterDataSeeder::class);

    expect(Severity::where('code', 'CRITICAL')->exists())->toBeTrue()
        ->and(Priority::where('code', 'URGENT')->exists())->toBeTrue()
        ->and(Status::where(['module' => 'incident', 'code' => 'SUBMITTED'])->exists())->toBeTrue()
        ->and(RiskMatrixLevel::count())->toBe(25);
});

it('creates severity and risk matrix records', function () {
    $admin = qhsseMasterAdmin();

    $this->actingAs($admin)->post(route('core.severities.store'), [
        'code' => 'MINOR',
        'name' => 'Minor',
        'level' => 1,
        'color' => 'green',
        'description' => 'Minor severity',
        'is_active' => true,
    ])->assertRedirect(route('core.severities.index'));

    $this->actingAs($admin)->post(route('core.risk-matrix.store'), [
        'likelihood' => 5,
        'consequence' => 5,
        'score' => 25,
        'level' => 'Extreme',
        'color' => 'red',
        'description' => 'Extreme risk',
        'is_active' => true,
    ])->assertRedirect(route('core.risk-matrix.index'));

    $this->assertDatabaseHas('severities', ['code' => 'MINOR']);
    $this->assertDatabaseHas('risk_matrix_levels', ['likelihood' => 5, 'consequence' => 5, 'level' => 'Extreme']);
});

it('blocks qhsse master data access without permission', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->get(route('core.severities.index'))->assertForbidden();
});
