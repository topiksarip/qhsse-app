<?php

use App\Core\Notifications\NotificationService;
use App\Models\Core\Notifications\CoreNotification;
use App\Models\Modules\LegalCompliance\LegalObligation;
use App\Models\Modules\LegalCompliance\LegalRegister;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;

uses(RefreshDatabase::class)->beforeEach(function () {
    $this->seed([
        \Database\Seeders\RolesAndPermissionsSeeder::class,
        \Database\Seeders\NotificationTemplateSeeder::class,
    ]);
});

/**
 * Modul 14 WS-2: CheckOverdueObligations command + schedule (G2 — compliance critical).
 */
it('sends notification for overdue legal obligations (WS-2)', function () {
    $owner = User::factory()->create(['is_active' => true]);
    $owner->assignRole('QHSSE Manager');
    $site = \App\Models\Core\MasterData\Site::factory()->create();

    $register = \App\Models\Modules\LegalCompliance\LegalRegister::create([
        'register_number' => 'LEG-TEST-001',
        'title' => 'Test Register',
        'regulation_name' => 'Test Reg',
        'issuing_body' => 'Kemenaker',
        'category' => 'national',
        'regulation_number' => 'UU-001',
        'owner_id' => $owner->id,
        'site_id' => $site->id,
        'status' => 'active',
    ]);

    $overdue = \App\Models\Modules\LegalCompliance\LegalObligation::create([
        'legal_register_id' => $register->id,
        'obligation_description' => 'Submit annual report',
        'frequency' => 'annual',
        'status' => 'pending',
        'next_due' => now()->subDays(3)->toDateString(),
    ]);

    Artisan::call('legal:check-overdue');

    expect(CoreNotification::where('type', 'legal.obligation.overdue')
        ->where('reference_id', $overdue->id)
        ->exists())->toBeTrue();
});

it('does not duplicate overdue notification on same day (WS-2)', function () {
    $owner = User::factory()->create(['is_active' => true]);
    $owner->assignRole('QHSSE Manager');
    $site = \App\Models\Core\MasterData\Site::factory()->create();

    $register = \App\Models\Modules\LegalCompliance\LegalRegister::create([
        'register_number' => 'LEG-TEST-002',
        'title' => 'Test Register 2',
        'regulation_name' => 'Test Reg 2',
        'issuing_body' => 'Kemenaker',
        'category' => 'national',
        'regulation_number' => 'UU-002',
        'owner_id' => $owner->id,
        'site_id' => $site->id,
        'status' => 'active',
    ]);

    $overdue = \App\Models\Modules\LegalCompliance\LegalObligation::create([
        'legal_register_id' => $register->id,
        'obligation_description' => 'Submit report',
        'frequency' => 'annual',
        'status' => 'pending',
        'next_due' => now()->subDays(1)->toDateString(),
    ]);

    Artisan::call('legal:check-overdue');
    Artisan::call('legal:check-overdue');

    $count = CoreNotification::where('type', 'legal.obligation.overdue')
        ->where('reference_id', $overdue->id)
        ->count();

    expect($count)->toBe(1); // idempotencyKey prevents duplicate same-day
});
