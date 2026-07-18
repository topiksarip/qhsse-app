<?php

use App\Models\Modules\Asset\Asset;
use App\Models\Core\Users\Employee;
use App\Models\Modules\Permit\PermitWorker;
use App\Models\Modules\Permit\PermitAsset;
use App\Models\Modules\Permit\Permit;
use App\Models\User;
use App\Models\Core\MasterData\Site;
use Database\Factories\Modules\Permit\PermitFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\post;
use function Pest\Laravel\put;

uses(RefreshDatabase::class)->beforeEach(function () {
    $this->seed([
        \Database\Seeders\RolesAndPermissionsSeeder::class,
        \Database\Seeders\NumberingFormatSeeder::class,
        \Database\Seeders\NotificationTemplateSeeder::class,
        \Database\Seeders\PermitWorkflowSeeder::class,
    ]);
});

it('requires at least one worker on permit creation', function () {
    $manager = User::factory()->create();
    $manager->assignRole('QHSSE Manager');
    $site = Site::factory()->create();

    actingAs($manager);
    post(route('permit.work.store'), [
        'type' => 'hot_work',
        'title' => 'Test PTW',
        'description' => 'Desc',
        'work_description' => 'Work desc',
        'site_id' => $site->id,
        'work_location' => 'Building A',
        'start_datetime' => now()->addHour()->format('Y-m-d\TH:i'),
        'end_datetime' => now()->addHours(9)->format('Y-m-d\TH:i'),
        'worker_ids' => [],
        'asset_ids' => [],
    ])->assertSessionHasErrors('worker_ids');

    expect(Permit::count())->toBe(0);
});

it('creates permit with workers (min 1) and optional assets + roles', function () {
    $manager = User::factory()->create();
    $manager->assignRole('QHSSE Manager');
    $site = Site::factory()->create();
    $e1 = Employee::factory()->create();
    $e2 = Employee::factory()->create();
    $asset = Asset::factory()->create();

    actingAs($manager);
    post(route('permit.work.store'), [
        'type' => 'hot_work',
        'title' => 'Test PTW with workers',
        'description' => 'Desc',
        'work_description' => 'Work desc',
        'site_id' => $site->id,
        'work_location' => 'Building A',
        'start_datetime' => now()->addHour()->format('Y-m-d\TH:i'),
        'end_datetime' => now()->addHours(9)->format('Y-m-d\TH:i'),
        'risk_level' => 'medium',
        'worker_ids' => [$e1->id, $e2->id],
        'worker_roles' => [$e1->id => 'pengawas', $e2->id => 'operator'],
        'asset_ids' => [$asset->id],
        'asset_roles' => [$asset->id => 'alat berat'],
    ])->assertRedirect();

    $permit = Permit::where('title', 'Test PTW with workers')->firstOrFail();
    expect(PermitWorker::where('permit_id', $permit->id)->count())->toBe(2);
    expect(PermitAsset::where('permit_id', $permit->id)->count())->toBe(1);

    $pw = PermitWorker::where('permit_id', $permit->id)->where('employee_id', $e1->id)->first();
    expect($pw->role)->toBe('pengawas');
    $pa = PermitAsset::where('permit_id', $permit->id)->where('asset_id', $asset->id)->first();
    expect($pa->role)->toBe('alat berat');
});

it('allows permit creation without assets', function () {
    $manager = User::factory()->create();
    $manager->assignRole('QHSSE Manager');
    $site = Site::factory()->create();
    $e1 = Employee::factory()->create();

    actingAs($manager);
    post(route('permit.work.store'), [
        'type' => 'other',
        'title' => 'No-asset PTW',
        'description' => 'Desc',
        'work_description' => 'Work desc',
        'site_id' => $site->id,
        'work_location' => 'Warehouse',
        'start_datetime' => now()->addHour()->format('Y-m-d\TH:i'),
        'end_datetime' => now()->addHours(5)->format('Y-m-d\TH:i'),
        'worker_ids' => [$e1->id],
        'asset_ids' => [],
    ])->assertRedirect();

    $permit = Permit::where('title', 'No-asset PTW')->firstOrFail();
    expect(PermitAsset::where('permit_id', $permit->id)->count())->toBe(0);
    expect(PermitWorker::where('permit_id', $permit->id)->count())->toBe(1);
});

it('updates pivot workers and assets when provided', function () {
    $manager = User::factory()->create();
    $manager->assignRole('QHSSE Manager');
    $permit = PermitFactory::new()->create(['status' => 'draft']);
    $e1 = Employee::factory()->create();
    $e2 = Employee::factory()->create();
    $asset = Asset::factory()->create();

    // seed existing worker to ensure old rows are replaced
    PermitWorker::create(['permit_id' => $permit->id, 'employee_id' => Employee::factory()->create()->id, 'role' => null]);

    actingAs($manager);
    put(route('permit.work.update', $permit), [
        'type' => $permit->type,
        'title' => $permit->title,
        'site_id' => $permit->site_id,
        'description' => 'Desc',
        'work_description' => 'Work desc',
        'work_location' => $permit->work_location,
        'start_datetime' => \Carbon\Carbon::parse($permit->start_datetime)->format('Y-m-d\TH:i'),
        'end_datetime' => \Carbon\Carbon::parse($permit->end_datetime)->format('Y-m-d\TH:i'),
        'worker_ids' => [$e1->id, $e2->id],
        'worker_roles' => [$e1->id => 'pengawas'],
        'asset_ids' => [$asset->id],
    ])->assertRedirect();

    expect(PermitWorker::where('permit_id', $permit->id)->count())->toBe(2);
    expect(PermitAsset::where('permit_id', $permit->id)->count())->toBe(1);
    expect(PermitWorker::where('permit_id', $permit->id)->where('employee_id', $e1->id)->first()->role)->toBe('pengawas');
});
