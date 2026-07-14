<?php

use App\Core\Activity\ActivityService;
use App\Models\Core\Audit\AuditLog;
use App\Models\Core\Comments\Comment;
use App\Models\Core\Files\ManagedFile;
use App\Models\Core\MasterData\Area;
use App\Models\Core\MasterData\Department;
use App\Models\Core\MasterData\Position;
use App\Models\Core\MasterData\Site;
use App\Models\Core\Notifications\CoreNotification;
use App\Models\Core\Numbering\GeneratedNumber;
use App\Models\Core\Users\Employee;
use App\Models\Modules\Asset\Asset;
use App\Models\Modules\Asset\AssetCertificate;
use App\Models\Modules\Asset\AssetInspection;
use App\Models\User;
use Database\Seeders\NotificationTemplateSeeder;
use Database\Seeders\NumberingFormatSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\QueryException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->seed([RolesAndPermissionsSeeder::class, NumberingFormatSeeder::class]);
    Storage::fake('local');
});

function assetActor(string $role, Site $site, Department $department): User
{
    $position = Position::factory()->for($department)->create();
    $employee = Employee::factory()->create([
        'site_id' => $site->id,
        'department_id' => $department->id,
        'position_id' => $position->id,
    ]);
    $user = User::factory()->linkedToEmployee($employee)->create();
    $user->assignRole($role);

    return $user;
}

function assetRecord(User $actor, Site $site, ?Department $department = null, ?Area $area = null, array $overrides = []): Asset
{
    static $sequence = 0;
    $sequence++;

    return Asset::create(array_merge([
        'asset_number' => sprintf('AST-TEST-%04d', $sequence),
        'name' => "Test Asset {$sequence}",
        'category' => 'equipment',
        'site_id' => $site->id,
        'department_id' => $department?->id,
        'area_id' => $area?->id,
        'status' => 'active',
        'safety_critical' => false,
        'created_by' => $actor->id,
        'updated_by' => $actor->id,
    ], $overrides));
}

function certificateRecord(Asset $asset, User $actor, array $overrides = []): AssetCertificate
{
    return AssetCertificate::create(array_merge([
        'asset_id' => $asset->id,
        'certificate_type' => 'Calibration',
        'certificate_number' => 'CERT-'.$asset->id,
        'issuing_body' => 'Independent Inspector',
        'issued_date' => today()->subDay(),
        'expiry_date' => today()->addYear(),
        'status' => 'valid',
        'created_by' => $actor->id,
        'updated_by' => $actor->id,
    ], $overrides));
}

function inspectionRecord(Asset $asset, User $actor, array $overrides = []): AssetInspection
{
    return AssetInspection::create(array_merge([
        'asset_id' => $asset->id,
        'inspection_date' => today(),
        'inspector_id' => $actor->id,
        'result' => 'pass',
        'created_by' => $actor->id,
        'updated_by' => $actor->id,
    ], $overrides));
}

it('applies the same fail-closed organizational scope to index detail and export', function () {
    $siteA = Site::factory()->create();
    $siteB = Site::factory()->create();
    $departmentA = Department::factory()->for($siteA)->create();
    $departmentB = Department::factory()->for($siteB)->create();
    $officer = assetActor('QHSSE Officer', $siteA, $departmentA);
    $own = assetRecord($officer, $siteA, $departmentA);
    $other = assetRecord($officer, $siteB, $departmentB);

    $this->actingAs($officer)->get(route('assets.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->has('assets.data', 1)
            ->where('assets.data.0.id', $own->id));

    $this->get(route('assets.show', $own))->assertOk();
    $this->get(route('assets.show', $other))->assertForbidden();

    $csv = $this->get(route('assets.export'))->assertOk()->streamedContent();
    expect($csv)->toContain($own->asset_number)->not->toContain($other->asset_number);
});

it('treats Employee Reporter own scope as department visibility and fails closed without employee context', function () {
    $site = Site::factory()->create();
    $department = Department::factory()->for($site)->create();
    $employee = assetActor('Employee / Reporter', $site, $department);
    $asset = assetRecord($employee, $site, $department);

    $this->actingAs($employee)->get(route('assets.show', $asset))->assertOk();

    $unlinked = User::factory()->create();
    $unlinked->assignRole('Employee / Reporter');
    $this->actingAs($unlinked)->get(route('assets.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->has('assets.data', 0));
});

it('returns complete and organizationally scoped asset form contracts', function () {
    $siteA = Site::factory()->create();
    $siteB = Site::factory()->create();
    $departmentA = Department::factory()->for($siteA)->create();
    Department::factory()->for($siteB)->create();
    Area::factory()->for($siteA)->create();
    Area::factory()->for($siteB)->create();
    $officer = assetActor('QHSSE Officer', $siteA, $departmentA);

    $this->actingAs($officer)->get(route('assets.create'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Modules/Asset/CreateOrEdit')
            ->has('sites', 1)
            ->has('areas', 1)
            ->has('departments', 1)
            ->has('categories')
            ->has('statuses'));
});

it('rejects mismatched area and department location payloads', function () {
    $siteA = Site::factory()->create();
    $siteB = Site::factory()->create();
    $departmentA = Department::factory()->for($siteA)->create();
    $departmentB = Department::factory()->for($siteB)->create();
    $areaB = Area::factory()->for($siteB)->create();
    $officer = assetActor('QHSSE Officer', $siteA, $departmentA);

    $this->actingAs($officer)->from(route('assets.create'))->post(route('assets.store'), [
        'name' => 'Cross-scope asset',
        'category' => 'equipment',
        'site_id' => $siteA->id,
        'area_id' => $areaB->id,
        'department_id' => $departmentB->id,
        'status' => 'active',
    ])->assertRedirect(route('assets.create'))->assertSessionHasErrors(['area_id', 'department_id']);

    expect(Asset::query()->count())->toBe(0);
});

it('stores certificate evidence privately and exposes a consistent show contract', function () {
    $site = Site::factory()->create();
    $department = Department::factory()->for($site)->create();
    $admin = assetActor('Super Admin', $site, $department);
    $asset = assetRecord($admin, $site, $department);

    $this->actingAs($admin)->post(route('assets.certificates.store', $asset), [
        'certificate_type' => 'Calibration',
        'certificate_number' => 'CAL-001',
        'issuing_body' => 'Sucofindo',
        'issued_date' => today()->toDateString(),
        'expiry_date' => today()->addYear()->toDateString(),
        'certificate_file' => UploadedFile::fake()->create('certificate.pdf', 100, 'application/pdf'),
    ])->assertRedirect();

    $certificate = AssetCertificate::query()->firstOrFail();
    $file = ManagedFile::query()->firstOrFail();
    expect($file->disk)->toBe('local')
        ->and($file->module_name)->toBe('asset')
        ->and($file->reference_id)->toBe($asset->id)
        ->and($certificate->certificate_file_id)->toBe($file->id);
    Storage::disk('local')->assertExists($file->path);

    $this->get(route('assets.certificates.show', [$asset, $certificate]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('certificate.certificate_file.id', $file->id)
            ->where('certificate.issuing_body', 'Sucofindo'));
});

it('blocks cross-asset nested certificate and inspection access', function () {
    $site = Site::factory()->create();
    $department = Department::factory()->for($site)->create();
    $admin = assetActor('Super Admin', $site, $department);
    $assetA = assetRecord($admin, $site, $department);
    $assetB = assetRecord($admin, $site, $department);
    $certificate = certificateRecord($assetB, $admin);
    $inspection = inspectionRecord($assetB, $admin);

    $this->actingAs($admin)
        ->get(route('assets.certificates.show', [$assetA, $certificate]))
        ->assertNotFound();
    $this->get(route('assets.inspections.show', [$assetA, $inspection]))
        ->assertNotFound();
});

it('renders and accepts the complete inspection form contract', function () {
    $site = Site::factory()->create();
    $department = Department::factory()->for($site)->create();
    $officer = assetActor('QHSSE Officer', $site, $department);
    $asset = assetRecord($officer, $site, $department);

    $this->actingAs($officer)->get(route('assets.inspections.create', $asset))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->has('inspectors', 1)
            ->has('results'));

    $nextDate = today()->addMonth()->toDateString();
    $this->post(route('assets.inspections.store', $asset), [
        'inspection_date' => today()->toDateString(),
        'inspector_id' => $officer->id,
        'result' => 'maintenance_required',
        'findings' => 'Guard requires repair.',
        'next_inspection_date' => $nextDate,
    ])->assertRedirect();

    expect(AssetInspection::query()->firstOrFail()->inspector_id)->toBe($officer->id)
        ->and($asset->fresh()->next_inspection_date->toDateString())->toBe($nextDate);
});

it('records field-level audit logs for asset certificate and inspection changes', function () {
    $site = Site::factory()->create();
    $department = Department::factory()->for($site)->create();
    $admin = assetActor('Super Admin', $site, $department);
    $asset = assetRecord($admin, $site, $department);
    $certificate = certificateRecord($asset, $admin);
    $inspection = inspectionRecord($asset, $admin);

    expect(AuditLog::query()->where('module_name', 'asset')->where('auditable_id', $asset->id)->exists())->toBeTrue()
        ->and(AuditLog::query()->where('module_name', 'asset_certificate')->where('auditable_id', $certificate->id)->exists())->toBeTrue()
        ->and(AuditLog::query()->where('module_name', 'asset_inspection')->where('auditable_id', $inspection->id)->exists())->toBeTrue();
});

it('rolls back asset creation and numbering when activity logging fails', function () {
    $site = Site::factory()->create();
    $department = Department::factory()->for($site)->create();
    $admin = assetActor('Super Admin', $site, $department);
    $mock = Mockery::mock(ActivityService::class);
    $mock->shouldReceive('log')->once()->andThrow(new RuntimeException('activity unavailable'));
    $this->app->instance(ActivityService::class, $mock);

    $this->withoutExceptionHandling();
    try {
        $this->actingAs($admin)->post(route('assets.store'), [
            'name' => 'Transactional Asset',
            'category' => 'equipment',
            'site_id' => $site->id,
            'department_id' => $department->id,
            'status' => 'active',
        ]);
    } catch (RuntimeException $exception) {
        expect($exception->getMessage())->toBe('activity unavailable');
    }

    expect(Asset::query()->count())->toBe(0)
        ->and(GeneratedNumber::query()->where('module_name', 'asset')->count())->toBe(0);
});

it('uses status lifecycle without destructive asset or compliance-history routes', function () {
    $site = Site::factory()->create();
    $department = Department::factory()->for($site)->create();
    $officer = assetActor('QHSSE Officer', $site, $department);
    $manager = assetActor('QHSSE Manager', $site, $department);
    $asset = assetRecord($manager, $site, $department);

    expect(Route::has('assets.destroy'))->toBeFalse()
        ->and(Route::has('assets.certificates.destroy'))->toBeFalse()
        ->and(Route::has('assets.inspections.destroy'))->toBeFalse();

    $this->actingAs($officer)->post(route('assets.decommission', $asset))->assertForbidden();
    $this->actingAs($manager)->post(route('assets.decommission', $asset))->assertRedirect();
    expect($asset->fresh()->status)->toBe('decommissioned');

    $this->patch(route('assets.status', $asset), ['status' => 'active'])->assertForbidden();
    $this->put(route('assets.update', $asset), ['name' => 'Must stay locked'])->assertForbidden();
});

it('preserves asset certificate and inspection records without soft-delete columns', function () {
    expect(Schema::hasColumn('assets', 'deleted_at'))->toBeFalse()
        ->and(Schema::hasColumn('asset_certificates', 'deleted_at'))->toBeFalse()
        ->and(Schema::hasColumn('asset_inspections', 'deleted_at'))->toBeFalse()
        ->and(in_array(SoftDeletes::class, class_uses_recursive(Asset::class), true))->toBeFalse()
        ->and(in_array(SoftDeletes::class, class_uses_recursive(AssetCertificate::class), true))->toBeFalse()
        ->and(in_array(SoftDeletes::class, class_uses_recursive(AssetInspection::class), true))->toBeFalse();
});

it('authorizes certificate downloads against the exact asset certificate and private file', function () {
    $site = Site::factory()->create();
    $department = Department::factory()->for($site)->create();
    $admin = assetActor('Super Admin', $site, $department);
    $asset = assetRecord($admin, $site, $department);

    foreach (['CERT-A', 'CERT-B'] as $number) {
        $this->actingAs($admin)->post(route('assets.certificates.store', $asset), [
            'certificate_type' => 'Calibration',
            'certificate_number' => $number,
            'issuing_body' => 'Sucofindo',
            'issued_date' => today()->toDateString(),
            'certificate_file' => UploadedFile::fake()->create("{$number}.pdf", 20, 'application/pdf'),
        ])->assertRedirect();
    }

    [$certificateA, $certificateB] = AssetCertificate::query()->orderBy('id')->get();
    $this->get(route('assets.certificates.files.download', [$asset, $certificateA, $certificateA->certificate_file_id]))
        ->assertDownload('CERT-A.pdf');
    $this->get(route('assets.certificates.files.download', [$asset, $certificateA, $certificateB->certificate_file_id]))
        ->assertNotFound();
});

it('prefills CAPA creation from a failed asset inspection using source linkage', function () {
    $site = Site::factory()->create();
    $department = Department::factory()->for($site)->create();
    $officer = assetActor('QHSSE Officer', $site, $department);
    $asset = assetRecord($officer, $site, $department);
    $inspection = inspectionRecord($asset, $officer, ['result' => 'fail', 'findings' => 'Guard is broken']);

    $redirect = $this->actingAs($officer)->get(route('assets.inspections.create-capa', [$asset, $inspection]))
        ->assertRedirect();
    $location = $redirect->headers->get('Location');

    $this->get($location)->assertOk()->assertInertia(fn (Assert $page) => $page
        ->component('Modules/Capa/Form')
        ->where('prefill.source_module', 'asset_inspection')
        ->where('prefill.source_reference_id', $inspection->id)
        ->where('prefill.site_id', $site->id)
        ->where('prefill.department_id', $department->id)
        ->where('prefill.description', 'Guard is broken'));
});

it('exposes resource-scoped comments and activity on asset detail', function () {
    $siteA = Site::factory()->create();
    $siteB = Site::factory()->create();
    $departmentA = Department::factory()->for($siteA)->create();
    $departmentB = Department::factory()->for($siteB)->create();
    $officer = assetActor('QHSSE Officer', $siteA, $departmentA);
    $assetA = assetRecord($officer, $siteA, $departmentA);
    $assetB = assetRecord($officer, $siteB, $departmentB);

    Comment::create(['module_name' => 'asset', 'reference_id' => $assetA->id, 'body' => 'Scoped note', 'author_id' => $officer->id]);
    app(ActivityService::class)->log('asset', $assetA->id, 'asset.tested', 'Scoped activity', $officer);

    $this->actingAs($officer)->get(route('assets.show', $assetA))->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('comments.0.body', 'Scoped note')
            ->where('activities.0.description', 'Scoped activity'));

    $this->post(route('assets.comments.store', $assetB), ['body' => 'Cross-scope note'])->assertForbidden();
    $this->post(route('assets.comments.store', $assetA), ['body' => 'Authorized note'])->assertRedirect();
    expect(Comment::query()->where('reference_id', $assetA->id)->where('body', 'Authorized note')->exists())->toBeTrue();
});

it('rejects an inspector outside the asset site and role boundary', function () {
    $siteA = Site::factory()->create();
    $siteB = Site::factory()->create();
    $departmentA = Department::factory()->for($siteA)->create();
    $departmentB = Department::factory()->for($siteB)->create();
    $officerA = assetActor('QHSSE Officer', $siteA, $departmentA);
    $officerB = assetActor('QHSSE Officer', $siteB, $departmentB);
    $asset = assetRecord($officerA, $siteA, $departmentA);

    $this->actingAs($officerA)->post(route('assets.inspections.store', $asset), [
        'inspection_date' => today()->toDateString(),
        'inspector_id' => $officerB->id,
        'result' => 'pass',
    ])->assertSessionHasErrors('inspector_id');

    expect(AssetInspection::query()->count())->toBe(0);
});

it('persists valid status when a certificate is renewed without an expiry date', function () {
    $site = Site::factory()->create();
    $department = Department::factory()->for($site)->create();
    $admin = assetActor('Super Admin', $site, $department);
    $asset = assetRecord($admin, $site, $department);
    $certificate = certificateRecord($asset, $admin, ['status' => 'expired', 'expiry_date' => today()->subDay()]);

    $this->actingAs($admin)->put(route('assets.certificates.update', [$asset, $certificate]), [
        'certificate_type' => $certificate->certificate_type,
        'certificate_number' => $certificate->certificate_number,
        'issuing_body' => $certificate->issuing_body,
        'issued_date' => today()->subYear()->toDateString(),
        'expiry_date' => null,
    ])->assertRedirect();

    expect($certificate->fresh()->status)->toBe('valid');
});

it('exports the complete operational asset register contract', function () {
    $site = Site::factory()->create();
    $department = Department::factory()->for($site)->create();
    $admin = assetActor('Super Admin', $site, $department);
    $asset = assetRecord($admin, $site, $department, null, [
        'name' => '=HYPERLINK("https://example.invalid","click")',
    ]);
    certificateRecord($asset, $admin, ['status' => 'expired']);
    inspectionRecord($asset, $admin, ['next_inspection_date' => today()->addMonth()]);

    $csv = $this->actingAs($admin)->get(route('assets.export'))->assertOk()->streamedContent();
    expect($csv)->toContain('Total Sertifikat')
        ->and($csv)->toContain('Sertifikat Expired')
        ->and($csv)->toContain('Inspeksi Terakhir')
        ->and($csv)->toContain($asset->asset_number)
        ->and($csv)->toContain("'=HYPERLINK")
        ->and($csv)->not->toContain("\n=HYPERLINK");
});

it('updates certificate status and sends due notifications idempotently', function () {
    $this->seed(NotificationTemplateSeeder::class);
    $site = Site::factory()->create();
    $department = Department::factory()->for($site)->create();
    $officer = assetActor('QHSSE Officer', $site, $department);
    $manager = assetActor('QHSSE Manager', $site, $department);
    $asset = assetRecord($officer, $site, $department, null, ['safety_critical' => true]);
    $certificate = certificateRecord($asset, $officer, ['status' => 'valid', 'expiry_date' => today()->addDays(7)]);
    inspectionRecord($asset, $officer, ['next_inspection_date' => today()->addDays(7)]);

    $this->artisan('assets:check-certificates')->assertSuccessful();
    $this->artisan('assets:check-certificates')->assertSuccessful();
    $this->artisan('assets:check-inspections')->assertSuccessful();
    $this->artisan('assets:check-inspections')->assertSuccessful();

    expect($certificate->fresh()->status)->toBe('expiring_critical')
        ->and(CoreNotification::query()->where('type', 'asset.certificate.expiring_critical')->count())->toBe(2)
        ->and(CoreNotification::query()->where('type', 'asset.inspection.due')->count())->toBe(2)
        ->and(CoreNotification::query()->whereNotNull('idempotency_key')->count())->toBe(4)
        ->and(CoreNotification::query()->pluck('recipient_id')->unique()->sort()->values()->all())
        ->toBe(collect([$officer->id, $manager->id])->sort()->values()->all());
});

it('does not expose asset certificate evidence through the generic core file endpoint', function () {
    $siteA = Site::factory()->create();
    $siteB = Site::factory()->create();
    $departmentA = Department::factory()->for($siteA)->create();
    $departmentB = Department::factory()->for($siteB)->create();
    $officerA = assetActor('QHSSE Officer', $siteA, $departmentA);
    $officerB = assetActor('QHSSE Officer', $siteB, $departmentB);
    $assetB = assetRecord($officerB, $siteB, $departmentB);

    $this->actingAs($officerB)->post(route('assets.certificates.store', $assetB), [
        'certificate_type' => 'Calibration',
        'certificate_number' => 'CERT-PRIVATE',
        'issuing_body' => 'Independent Inspector',
        'issued_date' => today()->toDateString(),
        'certificate_file' => UploadedFile::fake()->create('private.pdf', 20, 'application/pdf'),
    ])->assertRedirect();

    $file = ManagedFile::query()->firstOrFail();

    $this->actingAs($officerA)
        ->get(route('core.files.download', $file))
        ->assertNotFound();
    $this->actingAs($officerB)
        ->get(route('core.files.index', ['module_name' => 'asset']))
        ->assertInertia(fn (Assert $page) => $page->has('files.data', 0));

    $admin = assetActor('Super Admin', $siteB, $departmentB);
    $this->actingAs($admin)
        ->delete(route('core.files.destroy', $file))
        ->assertNotFound();
    expect($file->fresh()->deleted_at)->toBeNull();
});

it('applies asset policy to the generic comments and activity endpoint', function () {
    $siteA = Site::factory()->create();
    $siteB = Site::factory()->create();
    $departmentA = Department::factory()->for($siteA)->create();
    $departmentB = Department::factory()->for($siteB)->create();
    $officerA = assetActor('QHSSE Officer', $siteA, $departmentA);
    $officerB = assetActor('QHSSE Officer', $siteB, $departmentB);
    $assetB = assetRecord($officerB, $siteB, $departmentB);

    Comment::create([
        'module_name' => 'asset',
        'reference_id' => $assetB->id,
        'author_id' => $officerB->id,
        'body' => 'Restricted activity context',
    ]);

    $query = ['module_name' => 'asset', 'reference_id' => $assetB->id];

    $this->actingAs($officerA)
        ->get(route('core.comments-activity.index', $query))
        ->assertForbidden();
    $this->post(route('core.comments.store'), [...$query, 'body' => 'Cross-scope comment'])
        ->assertForbidden();

    $admin = assetActor('Super Admin', $siteB, $departmentB);
    $comment = Comment::query()->where('body', 'Restricted activity context')->firstOrFail();
    $this->actingAs($admin)
        ->delete(route('core.comments.destroy', $comment))
        ->assertForbidden();

    expect(Comment::query()->where('body', 'Cross-scope comment')->exists())->toBeFalse();
});

it('always creates assets in active status through the lifecycle boundary', function () {
    $site = Site::factory()->create();
    $department = Department::factory()->for($site)->create();
    $admin = assetActor('Super Admin', $site, $department);

    $this->actingAs($admin)->post(route('assets.store'), [
        'name' => 'Lifecycle controlled asset',
        'category' => 'machinery',
        'site_id' => $site->id,
        'department_id' => $department->id,
        'status' => 'inactive',
    ])->assertRedirect();

    expect(Asset::query()->where('name', 'Lifecycle controlled asset')->firstOrFail()->status)->toBe('active');
});

it('returns safe certificate evidence and per-record ability props for Inertia pages', function () {
    $site = Site::factory()->create();
    $department = Department::factory()->for($site)->create();
    $officer = assetActor('QHSSE Officer', $site, $department);
    $asset = assetRecord($officer, $site, $department);

    $this->actingAs($officer)->post(route('assets.certificates.store', $asset), [
        'certificate_type' => 'Calibration',
        'certificate_number' => 'CERT-INERTIA',
        'issuing_body' => 'Sucofindo',
        'issued_date' => today()->toDateString(),
        'certificate_file' => UploadedFile::fake()->create('inertia.pdf', 20, 'application/pdf'),
    ])->assertRedirect();

    $certificate = AssetCertificate::query()->firstOrFail();

    $this->get(route('assets.certificates.index', $asset))
        ->assertInertia(fn (Assert $page) => $page
            ->where('certificates.0.can_update', true)
            ->missing('certificates.0.deleted_at'));

    $this->get(route('assets.certificates.show', [$asset, $certificate]))
        ->assertInertia(fn (Assert $page) => $page
            ->where('certificate.issuing_body', 'Sucofindo')
            ->where('certificate.certificate_file.original_name', 'inertia.pdf')
            ->where('certificate.certificate_file.download_url', route('assets.certificates.files.download', [$asset, $certificate, $certificate->certificate_file_id]))
            ->missing('certificate.certificate_file.path')
            ->missing('certificate.certificate_file.checksum'));
});

it('returns complete inspection Inertia props with source-based CAPA state', function () {
    $site = Site::factory()->create();
    $department = Department::factory()->for($site)->create();
    $officer = assetActor('QHSSE Officer', $site, $department);
    $asset = assetRecord($officer, $site, $department);
    $inspection = inspectionRecord($asset, $officer, [
        'result' => 'fail',
        'next_inspection_date' => today()->addMonth(),
    ]);

    $this->actingAs($officer)->get(route('assets.inspections.index', $asset))
        ->assertInertia(fn (Assert $page) => $page
            ->where('inspections.0.can_update', true)
            ->where('inspections.0.can_create_capa', true)
            ->where('inspections.0.capa_action', null));

    $this->get(route('assets.inspections.edit', [$asset, $inspection]))
        ->assertInertia(fn (Assert $page) => $page
            ->has('inspectors', 1)
            ->where('inspection.inspector_id', $officer->id)
            ->where('inspection.next_inspection_date', $inspection->next_inspection_date->toDateString()));

    $this->get(route('assets.show', $asset))
        ->assertInertia(fn (Assert $page) => $page
            ->where('asset.inspections.0.capa_action', null)
            ->where('can.changeStatus', true)
            ->where('can.comment', true));
});

it('logs out an inactive user with an existing asset session', function () {
    $site = Site::factory()->create();
    $department = Department::factory()->for($site)->create();
    $admin = assetActor('Super Admin', $site, $department);
    $admin->update(['is_active' => false]);

    $this->actingAs($admin)->get(route('assets.index'))
        ->assertRedirect(route('login'));

    $this->assertGuest();
});

it('prevents deleting a site that owns permanent asset compliance history', function () {
    $site = Site::factory()->create();
    $department = Department::factory()->for($site)->create();
    $admin = assetActor('Super Admin', $site, $department);
    $asset = assetRecord($admin, $site, $department);
    $certificate = certificateRecord($asset, $admin);
    $inspection = inspectionRecord($asset, $admin);

    expect(fn () => $site->delete())->toThrow(QueryException::class);

    expect($asset->fresh())->not->toBeNull()
        ->and($certificate->fresh())->not->toBeNull()
        ->and($inspection->fresh())->not->toBeNull();
});

it('assigns the exact asset permission matrix to supervisor and contractor', function () {
    $site = Site::factory()->create();
    $department = Department::factory()->for($site)->create();
    $supervisor = assetActor('Supervisor', $site, $department);
    $contractor = assetActor('Contractor', $site, $department);

    expect($supervisor->can('asset.management.view'))->toBeTrue()
        ->and($supervisor->can('asset.management.export'))->toBeTrue()
        ->and($supervisor->can('asset.management.create'))->toBeFalse()
        ->and($supervisor->can('asset.management.update'))->toBeFalse()
        ->and($supervisor->can('asset.certificates.view'))->toBeTrue()
        ->and($supervisor->can('asset.inspections.view'))->toBeTrue()
        ->and($contractor->can('asset.management.view'))->toBeTrue()
        ->and($contractor->can('asset.certificates.view'))->toBeFalse()
        ->and($contractor->can('asset.inspections.view'))->toBeFalse();
});

it('derives the parent due date from the latest inspection only', function () {
    $site = Site::factory()->create();
    $department = Department::factory()->for($site)->create();
    $officer = assetActor('QHSSE Officer', $site, $department);
    $asset = assetRecord($officer, $site, $department);
    $latestDue = today()->addMonths(2)->toDateString();

    $this->actingAs($officer)->post(route('assets.inspections.store', $asset), [
        'inspection_date' => today()->toDateString(),
        'inspector_id' => $officer->id,
        'result' => 'pass',
        'next_inspection_date' => $latestDue,
    ])->assertRedirect();
    $latestInspection = AssetInspection::query()->firstOrFail();

    $this->post(route('assets.inspections.store', $asset), [
        'inspection_date' => today()->subDay()->toDateString(),
        'inspector_id' => $officer->id,
        'result' => 'pass',
        'next_inspection_date' => today()->addWeek()->toDateString(),
    ])->assertRedirect();

    expect($asset->fresh()->next_inspection_date->toDateString())->toBe($latestDue);

    $this->put(route('assets.inspections.update', [$asset, $latestInspection]), [
        'inspection_date' => today()->toDateString(),
        'inspector_id' => $officer->id,
        'result' => 'pass',
        'next_inspection_date' => null,
    ])->assertRedirect();

    expect($asset->fresh()->next_inspection_date)->toBeNull();
});

it('surfaces certificate risk and failed inspections without capa on the asset register', function () {
    $site = Site::factory()->create();
    $department = Department::factory()->for($site)->create();
    $officer = assetActor('QHSSE Officer', $site, $department);
    $asset = assetRecord($officer, $site, $department);
    certificateRecord($asset, $officer, ['status' => 'expired']);
    certificateRecord($asset, $officer, [
        'certificate_number' => 'CERT-CRITICAL',
        'status' => 'expiring_critical',
    ]);
    inspectionRecord($asset, $officer, ['result' => 'fail']);

    $this->actingAs($officer)->get(route('assets.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('assets.data.0.id', $asset->id)
            ->where('assets.data.0.certificate_status', 'expired')
            ->where('assets.data.0.failed_inspections_without_capa', 1));
});
