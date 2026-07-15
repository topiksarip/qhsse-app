<?php

use App\Console\Commands\CheckExpiringPrequalification;
use App\Models\Core\Notifications\CoreNotification;
use App\Models\Modules\Contractor\Contractor;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed([
        \Database\Seeders\RolesAndPermissionsSeeder::class,
        \Database\Seeders\NumberingFormatSeeder::class,
        \Database\Seeders\NotificationTemplateSeeder::class,
    ]);
});

it('does not notify when no prequalification is expiring', function () {
    // Prequalified but far in the future (beyond 30 days)
    Contractor::factory()->create([
        'is_prequalified' => true,
        'prequalified_until' => now()->addYear(),
    ]);

    // Not prequalified at all
    Contractor::factory()->create([
        'is_prequalified' => false,
        'prequalified_until' => null,
    ]);

    $this->artisan(CheckExpiringPrequalification::class)
        ->assertSuccessful();

    expect(CoreNotification::where('type', 'contractor.expiring_soon')->count())->toBe(0);
});

it('notifies QHSSE team + creator for prequalifications expiring within 30 days', function () {
    $creator = User::factory()->create();
    $creator->assignRole('QHSSE Officer');

    $manager = User::factory()->create();
    $manager->assignRole('QHSSE Manager');

    $officer = User::factory()->create();
    $officer->assignRole('QHSSE Officer');

    Contractor::factory()->create([
        'is_prequalified' => true,
        'prequalified_until' => now()->addDays(10), // within 30-day window
        'created_by' => $creator->id,
    ]);

    $this->artisan(CheckExpiringPrequalification::class)
        ->assertSuccessful();

    $notifications = CoreNotification::where('type', 'contractor.expiring_soon')->get();

    // Creator must be notified (as contractor creator, also a QHSSE Officer)
    expect($notifications->where('recipient_id', $creator->id)->count())->toBeGreaterThanOrEqual(1)
        // Manager must be notified
        ->and($notifications->where('recipient_id', $manager->id)->count())->toBeGreaterThanOrEqual(1)
        // At least creator + manager (QHSSE team) receive the reminder
        ->and($notifications->count())->toBeGreaterThanOrEqual(2);
});

it('does not treat already-expired prequalification as expiring-soon', function () {
    Contractor::factory()->create([
        'is_prequalified' => true,
        'prequalified_until' => now()->subDays(5), // already expired
    ]);

    $this->artisan(CheckExpiringPrequalification::class)
        ->assertSuccessful();

    expect(CoreNotification::where('type', 'contractor.expiring_soon')->count())->toBe(0);
});
