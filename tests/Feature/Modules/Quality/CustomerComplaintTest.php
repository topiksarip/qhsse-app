<?php

use App\Models\Core\Activity\ActivityLog;
use App\Models\Core\Audit\AuditLog;
use App\Models\Core\MasterData\Severity;
use App\Models\Core\MasterData\Site;
use App\Models\Core\Users\Employee;
use App\Models\Modules\Quality\CustomerComplaint;
use App\Models\User;
use Database\Seeders\NumberingFormatSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;

use function Pest\Laravel\actingAs;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
    $this->seed(NumberingFormatSeeder::class);
    $this->admin = User::factory()->create();
    $this->admin->assignRole('Admin');
    $this->site = Site::factory()->create();
    $this->severity = Severity::factory()->create();
});

function complaintPayload(object $test, array $overrides = []): array
{
    return $overrides + [
        'customer_name' => 'PT Pelanggan Utama',
        'customer_contact' => 'qa@pelanggan.test',
        'title' => 'Produk tidak sesuai spesifikasi',
        'description' => 'Dimensi produk yang diterima tidak sesuai spesifikasi kontrak.',
        'site_id' => $test->site->id,
        'product_service' => 'Produk A',
        'severity_id' => $test->severity->id,
    ];
}

function complaintRecord(object $test, array $overrides = []): CustomerComplaint
{
    return CustomerComplaint::query()->create($overrides + complaintPayload($test, [
        'complaint_number' => 'CCR-TEST-'.fake()->unique()->numerify('####'),
        'status' => 'open',
    ]));
}

test('authorized quality user creates numbered complaint with audit and activity', function () {
    actingAs($this->admin);

    $response = $this->post(route('quality.complaints.store'), complaintPayload($this));
    $complaint = CustomerComplaint::query()->firstOrFail();

    $response->assertRedirect(route('quality.complaints.show', $complaint));
    expect($complaint->complaint_number)->toStartWith('CCR-')
        ->and($complaint->status)->toBe('open')
        ->and(AuditLog::query()->where('module_name', 'quality_complaint')->where('reference_id', $complaint->id)->where('event', 'created')->exists())->toBeTrue()
        ->and(ActivityLog::query()->where('module_name', 'quality_complaint')->where('reference_id', $complaint->id)->where('event', 'created')->exists())->toBeTrue();
});

test('complaint creation validates required fields and description length', function () {
    actingAs($this->admin);

    $this->post(route('quality.complaints.store'), complaintPayload($this, [
        'customer_name' => '',
        'description' => 'too short',
    ]))->assertSessionHasErrors(['customer_name', 'description']);

    expect(CustomerComplaint::count())->toBe(0);
});

test('view only user cannot create update close or export complaints', function () {
    $user = User::factory()->create();
    $user->givePermissionTo('quality.complaints.view', 'core.scope.all');
    $complaint = complaintRecord($this);
    actingAs($user);

    $this->get(route('quality.complaints.index'))->assertOk();
    $this->get(route('quality.complaints.create'))->assertForbidden();
    $this->put(route('quality.complaints.update', $complaint), complaintPayload($this))->assertForbidden();
    $this->post(route('quality.complaints.close', $complaint), ['resolution' => 'Resolved correctly.'])->assertForbidden();
    $this->get(route('quality.complaints.export'))->assertForbidden();
});

test('site scoped quality officer only sees own site in list detail and export', function () {
    $employee = Employee::factory()->forSite($this->site)->create();
    $officer = User::factory()->create(['employee_id' => $employee->id]);
    $officer->assignRole('QHSSE Officer');
    $visible = complaintRecord($this, ['title' => 'Visible Complaint']);
    $otherSite = Site::factory()->create();
    $hidden = complaintRecord($this, ['title' => 'Hidden Complaint', 'site_id' => $otherSite->id]);
    actingAs($officer);

    $this->get(route('quality.complaints.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->has('complaints.data', 1)
            ->where('complaints.data.0.id', $visible->id)
            ->has('sites', 1));
    $this->get(route('quality.complaints.show', $hidden))->assertForbidden();
    $csv = $this->get(route('quality.complaints.export'))->streamedContent();
    expect($csv)->toContain('Visible Complaint')->not->toContain('Hidden Complaint');
});

test('site scoped quality officer cannot create complaint for another site', function () {
    $employee = Employee::factory()->forSite($this->site)->create();
    $officer = User::factory()->create(['employee_id' => $employee->id]);
    $officer->assignRole('QHSSE Officer');
    actingAs($officer);

    $this->post(route('quality.complaints.store'), complaintPayload($this, [
        'site_id' => Site::factory()->create()->id,
    ]))->assertForbidden();

    expect(CustomerComplaint::count())->toBe(0);
});

test('close requires resolution and records closed state audit and activity', function () {
    $complaint = complaintRecord($this);
    actingAs($this->admin);

    $this->post(route('quality.complaints.close', $complaint))->assertSessionHasErrors('resolution');
    $this->post(route('quality.complaints.close', $complaint), [
        'resolution' => 'Produk pengganti telah dikirim dan diterima pelanggan.',
    ])->assertRedirect(route('quality.complaints.show', $complaint));

    $complaint->refresh();
    expect($complaint->status)->toBe('closed')
        ->and($complaint->closed_at)->not->toBeNull()
        ->and($complaint->resolution)->toContain('Produk pengganti')
        ->and(AuditLog::query()->where('module_name', 'quality_complaint')->where('reference_id', $complaint->id)->where('event', 'updated')->exists())->toBeTrue()
        ->and(ActivityLog::query()->where('module_name', 'quality_complaint')->where('reference_id', $complaint->id)->where('event', 'closed')->exists())->toBeTrue();
});

test('closed complaint cannot be closed or edited again', function () {
    $complaint = complaintRecord($this, [
        'status' => 'closed',
        'closed_at' => now(),
        'resolution' => 'Sudah diselesaikan.',
    ]);
    actingAs($this->admin);

    $this->post(route('quality.complaints.close', $complaint), ['resolution' => 'Attempt duplicate close.'])->assertForbidden();
    $this->get(route('quality.complaints.edit', $complaint))->assertForbidden();
});

test('export applies status filter', function () {
    complaintRecord($this, ['title' => 'Open Record']);
    complaintRecord($this, ['title' => 'Closed Record', 'status' => 'closed', 'closed_at' => now(), 'resolution' => 'Done']);
    actingAs($this->admin);

    $csv = $this->get(route('quality.complaints.export', ['status' => 'open']))->streamedContent();
    expect($csv)->toContain('Open Record')->not->toContain('Closed Record');
});

test('company scope without a site link fails closed', function () {
    $complaint = complaintRecord($this);
    $user = User::factory()->create();
    $user->givePermissionTo(['quality.complaints.view', 'core.scope.company']);
    actingAs($user);

    $this->get(route('quality.complaints.index'))
        ->assertInertia(fn ($page) => $page->where('complaints.total', 0));
    $this->get(route('quality.complaints.show', $complaint))->assertForbidden();
});
