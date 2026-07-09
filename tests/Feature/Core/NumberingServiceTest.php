<?php

use App\Core\Numbering\NumberingService;
use App\Models\Core\Numbering\GeneratedNumber;
use App\Models\Core\Numbering\NumberingCounter;
use App\Models\Core\Numbering\NumberingFormat;
use App\Models\User;
use Database\Seeders\NumberingFormatSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
});

function numberingAdmin(): User
{
    $user = User::factory()->create();
    $user->assignRole('Super Admin');

    return $user;
}

it('seeds baseline numbering formats for QHSSE modules', function () {
    $this->seed(NumberingFormatSeeder::class);

    expect(NumberingFormat::where('module_name', 'incident')->where('prefix', 'INC')->exists())->toBeTrue()
        ->and(NumberingFormat::where('module_name', 'permit')->where('include_site_code', true)->exists())->toBeTrue();
});

it('generates sequential yearly numbers without duplicates', function () {
    $admin = numberingAdmin();
    NumberingFormat::factory()->create([
        'module_name' => 'incident',
        'prefix' => 'INC',
        'padding' => 4,
        'separator' => '-',
        'reset_frequency' => 'yearly',
        'include_year' => true,
        'include_site_code' => false,
    ]);

    $service = app(NumberingService::class);

    $first = $service->generate('incident', $admin);
    $second = $service->generate('incident', $admin);

    expect($first->number)->toBe('INC-'.now()->year.'-0001')
        ->and($second->number)->toBe('INC-'.now()->year.'-0002')
        ->and(GeneratedNumber::pluck('number')->unique()->count())->toBe(2)
        ->and(NumberingCounter::where('module_name', 'incident')->first()->current_number)->toBe(2);
});

it('supports required site code in generated numbers', function () {
    $admin = numberingAdmin();
    NumberingFormat::factory()->create([
        'module_name' => 'permit',
        'prefix' => 'PTW',
        'padding' => 3,
        'separator' => '-',
        'reset_frequency' => 'yearly',
        'include_year' => true,
        'include_site_code' => true,
    ]);

    $number = app(NumberingService::class)->generate('permit', $admin, 'jkt');

    expect($number->number)->toBe('PTW-JKT-'.now()->year.'-001')
        ->and($number->site_code)->toBe('JKT');
});

it('blocks generation when site code is required but missing', function () {
    NumberingFormat::factory()->create([
        'module_name' => 'permit',
        'prefix' => 'PTW',
        'include_site_code' => true,
    ]);

    app(NumberingService::class)->generate('permit', numberingAdmin());
})->throws(RuntimeException::class);

it('allows authorized users to manage formats and generate from UI route', function () {
    $admin = numberingAdmin();

    $this->actingAs($admin)->post(route('core.numbering.store'), [
        'module_name' => 'quality',
        'prefix' => 'NCR',
        'padding' => 4,
        'separator' => '-',
        'reset_frequency' => 'yearly',
        'include_year' => true,
        'include_site_code' => false,
        'is_active' => true,
    ])->assertRedirect(route('core.numbering.index'));

    $this->actingAs($admin)->post(route('core.numbering.generate'), [
        'module_name' => 'quality',
    ])->assertRedirect(route('core.numbering.index'));

    $this->assertDatabaseHas('generated_numbers', ['module_name' => 'quality', 'number' => 'NCR-'.now()->year.'-0001']);
});

it('blocks numbering access without permission', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->get(route('core.numbering.index'))->assertForbidden();
});
