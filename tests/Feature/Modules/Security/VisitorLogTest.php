<?php

use App\Models\Core\Activity\ActivityLog;
use App\Models\Core\Audit\AuditLog;
use App\Models\Core\MasterData\Site;
use App\Models\Core\Users\Employee;
use App\Models\Modules\Security\VisitorLog;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Support\Carbon;

use function Pest\Laravel\actingAs;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
    $this->admin = User::factory()->create();
    $this->admin->assignRole('Admin');
    $this->site = Site::factory()->create();
    $this->host = Employee::factory()->forSite($this->site)->create();
});

function visitorPayload(object $test, array $overrides = []): array
{
    return $overrides + [
        'visitor_name' => 'Budi Visitor',
        'visitor_type' => 'KTP',
        'visitor_id_number' => '3174000012345678',
        'visitor_company' => 'PT Mitra',
        'visitor_phone' => '081234567890',
        'host_employee_id' => $test->host->id,
        'site_id' => $test->site->id,
        'purpose' => 'Pertemuan koordinasi proyek',
        'vehicle_number' => 'B 1234 CD',
        'checked_in_at' => Carbon::now()->subMinute()->format('Y-m-d H:i:s'),
        'notes' => 'Membawa laptop',
    ];
}

function visitorRecord(object $test, array $overrides = []): VisitorLog
{
    return VisitorLog::query()->create($overrides + visitorPayload($test, [
        'checked_in_by' => $test->admin->id,
        'status' => 'checked_in',
    ]));
}

test('authorized guard can check in visitor with audit and activity', function () {
    actingAs($this->admin);

    $response = $this->post(route('security.visitors.store'), visitorPayload($this));
    $visitor = VisitorLog::query()->firstOrFail();

    $response->assertRedirect(route('security.visitors.show', $visitor));
    expect($visitor->status)->toBe('checked_in')
        ->and($visitor->checked_in_by)->toBe($this->admin->id)
        ->and(AuditLog::query()->where('module_name', 'security_visitor')->where('reference_id', $visitor->id)->where('event', 'created')->exists())->toBeTrue()
        ->and(ActivityLog::query()->where('module_name', 'security_visitor')->where('reference_id', $visitor->id)->where('event', 'checked_in')->exists())->toBeTrue();
});

test('check in validates identity type and host belongs to selected site', function () {
    $otherSite = Site::factory()->create();
    $otherHost = Employee::factory()->forSite($otherSite)->create();
    actingAs($this->admin);

    $this->post(route('security.visitors.store'), visitorPayload($this, [
        'visitor_type' => 'vendor',
        'host_employee_id' => $otherHost->id,
    ]))->assertSessionHasErrors(['visitor_type', 'host_employee_id']);

    expect(VisitorLog::count())->toBe(0);
});

test('site scoped security officer only sees own site in list detail and export', function () {
    $officerEmployee = Employee::factory()->forSite($this->site)->create();
    $officer = User::factory()->create(['employee_id' => $officerEmployee->id]);
    $officer->assignRole('Security Officer');
    $visible = visitorRecord($this, ['visitor_name' => 'Visible Visitor']);
    $otherSite = Site::factory()->create();
    $hidden = visitorRecord($this, [
        'visitor_name' => 'Hidden Visitor',
        'site_id' => $otherSite->id,
        'host_employee_id' => Employee::factory()->forSite($otherSite)->create()->id,
    ]);
    actingAs($officer);

    $this->get(route('security.visitors.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->has('visitors.data', 1)
            ->where('visitors.data.0.id', $visible->id)
            ->has('sites', 1));
    $this->get(route('security.visitors.show', $hidden))->assertForbidden();
    $csv = $this->get(route('security.visitors.export'))->streamedContent();
    expect($csv)->toContain('Visible Visitor')->not->toContain('Hidden Visitor');
});

test('site scoped officer cannot create visitor for another site', function () {
    $employee = Employee::factory()->forSite($this->site)->create();
    $officer = User::factory()->create(['employee_id' => $employee->id]);
    $officer->assignRole('Security Officer');
    $otherSite = Site::factory()->create();
    $otherHost = Employee::factory()->forSite($otherSite)->create();
    actingAs($officer);

    $this->post(route('security.visitors.store'), visitorPayload($this, [
        'site_id' => $otherSite->id,
        'host_employee_id' => $otherHost->id,
    ]))->assertForbidden();

    expect(VisitorLog::count())->toBe(0);
});

test('check out records actor timestamp audit and activity', function () {
    $visitor = visitorRecord($this);
    actingAs($this->admin);

    $this->post(route('security.visitors.check-out', $visitor))
        ->assertRedirect(route('security.visitors.show', $visitor));

    $visitor->refresh();
    expect($visitor->status)->toBe('checked_out')
        ->and($visitor->checked_out_by)->toBe($this->admin->id)
        ->and($visitor->checked_out_at)->not->toBeNull()
        ->and(AuditLog::query()->where('module_name', 'security_visitor')->where('reference_id', $visitor->id)->where('event', 'updated')->exists())->toBeTrue()
        ->and(ActivityLog::query()->where('module_name', 'security_visitor')->where('reference_id', $visitor->id)->where('event', 'checked_out')->exists())->toBeTrue();
});

test('checked out visitor cannot be checked out or edited again', function () {
    $visitor = visitorRecord($this, [
        'status' => 'checked_out',
        'checked_out_at' => now(),
        'checked_out_by' => $this->admin->id,
    ]);
    actingAs($this->admin);

    $this->post(route('security.visitors.check-out', $visitor))->assertForbidden();
    $this->get(route('security.visitors.edit', $visitor))->assertForbidden();
});

test('user without visitor permission is blocked', function () {
    $user = User::factory()->create();
    actingAs($user);

    $this->get(route('security.visitors.index'))->assertForbidden();
    $this->post(route('security.visitors.store'), visitorPayload($this))->assertForbidden();
});

test('company scope without a site link fails closed', function () {
    $visitor = VisitorLog::factory()->create();
    $user = User::factory()->create();
    $user->givePermissionTo(['security.visitor.view', 'core.scope.company']);
    actingAs($user);

    $this->get(route('security.visitors.index'))
        ->assertInertia(fn ($page) => $page->where('visitors.total', 0));
    $this->get(route('security.visitors.show', $visitor))->assertForbidden();
});
