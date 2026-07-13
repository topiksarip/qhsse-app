<?php

use App\Models\Core\Audit\AuditLog;
use App\Models\Core\MasterData\Company;
use App\Models\Core\MasterData\Department;
use App\Models\Core\MasterData\Position;
use App\Models\Core\MasterData\Site;
use App\Models\Core\Users\Employee;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Http\UploadedFile;

use function Pest\Laravel\actingAs;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
    $this->admin = User::factory()->create(['is_active' => true]);
    $this->admin->assignRole('Admin');
});

function csvUpload(string $name, array $rows): UploadedFile
{
    $content = collect($rows)->map(function (array $row): string {
        $handle = fopen('php://temp', 'r+');
        fputcsv($handle, $row);
        rewind($handle);
        $line = stream_get_contents($handle);
        fclose($handle);

        return rtrim($line, "\r\n");
    })->implode("\n")."\n";

    return UploadedFile::fake()->createWithContent($name, $content);
}

test('admin dashboard returns correct identity counts and latest ten audit entries', function () {
    $sites = Site::factory()->count(2)->create();
    $company = Company::factory()->create();
    foreach (range(1, 3) as $index) {
        Employee::create([
            'employee_no' => "ADMIN-{$index}",
            'name' => "Employee {$index}",
            'site_id' => $sites->first()->id,
            'company_id' => $company->id,
            'is_active' => true,
        ]);
    }
    User::factory()->create(['is_active' => false]);
    foreach (range(1, 12) as $index) {
        AuditLog::create(['event' => "test_event_{$index}", 'actor_name' => 'System']);
    }
    actingAs($this->admin);

    $this->get(route('admin.dashboard'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Core/Admin/Dashboard')
            ->where('stats.sites', 2)
            ->where('stats.companies', 1)
            ->where('stats.employees', 3)
            ->where('stats.users', 2)
            ->where('stats.activeUsers', 1)
            ->has('recentActivity', 10));
});

test('admin tooling routes require backend permission', function () {
    $user = User::factory()->create();
    actingAs($user);

    $this->get(route('admin.dashboard'))->assertForbidden();
    $this->get(route('admin.import.create'))->assertForbidden();
    $this->post(route('admin.import.store', 'sites'), [
        'file' => csvUpload('sites.csv', [['code', 'name'], ['A', 'Site A']]),
    ])->assertForbidden();
});

test('inactive authenticated user is logged out by active middleware', function () {
    $this->admin->update(['is_active' => false]);
    actingAs($this->admin);

    $this->get(route('admin.dashboard'))->assertRedirect(route('login'));
    $this->assertGuest();
});

test('site import commits all valid rows and records safe audit summary', function () {
    actingAs($this->admin);

    $this->post(route('admin.import.store', 'sites'), [
        'file' => csvUpload('sites.csv', [
            ['code', 'name', 'address', 'is_active'],
            ['SITE-A', 'Plant A', 'Jakarta', 'true'],
            ['SITE-B', 'Plant B', 'Surabaya', 'false'],
        ]),
    ])->assertRedirect(route('admin.import.create'));

    $this->assertDatabaseHas('sites', ['code' => 'SITE-A', 'is_active' => true]);
    $this->assertDatabaseHas('sites', ['code' => 'SITE-B', 'is_active' => false]);
    $audit = AuditLog::where('event', 'bulk_import_completed')->firstOrFail();
    expect($audit->new_values)->toBe(['type' => 'sites', 'rows' => 2])
        ->and($audit->metadata)->toBe(['filename' => 'sites.csv']);
});

test('invalid row rejects the entire csv before any insert', function () {
    actingAs($this->admin);

    $this->from(route('admin.import.create'))->post(route('admin.import.store', 'sites'), [
        'file' => csvUpload('invalid.csv', [
            ['code', 'name'],
            ['VALID', 'Valid Site'],
            ['', 'Missing Code'],
        ]),
    ])->assertRedirect(route('admin.import.create'))->assertSessionHasErrors('rows.3');

    $this->assertDatabaseMissing('sites', ['code' => 'VALID']);
    $this->assertDatabaseMissing('audit_logs', ['event' => 'bulk_import_completed']);
});

test('duplicate values inside csv reject the entire import', function () {
    actingAs($this->admin);

    $this->post(route('admin.import.store', 'sites'), [
        'file' => csvUpload('duplicate.csv', [
            ['code', 'name'],
            ['DUP', 'First'],
            ['DUP', 'Second'],
        ]),
    ])->assertSessionHasErrors('rows.3');

    expect(Site::where('code', 'DUP')->count())->toBe(0);
});

test('department and employee imports resolve organization codes', function () {
    $site = Site::factory()->create(['code' => 'SITE-A', 'is_active' => true]);
    $company = Company::factory()->create(['code' => 'COMP', 'is_active' => true]);
    actingAs($this->admin);

    $this->post(route('admin.import.store', 'departments'), [
        'file' => csvUpload('departments.csv', [
            ['code', 'name', 'site_code', 'is_active'],
            ['HSE', 'Health Safety Environment', 'SITE-A', 'true'],
        ]),
    ])->assertRedirect();
    $department = Department::where('code', 'HSE')->firstOrFail();
    $position = Position::factory()->create([
        'department_id' => $department->id,
        'code' => 'OFFICER',
        'is_active' => true,
    ]);

    $this->post(route('admin.import.store', 'employees'), [
        'file' => csvUpload('employees.csv', [
            ['employee_no', 'name', 'email', 'phone', 'company_code', 'site_code', 'department_code', 'position_code', 'is_active'],
            ['EMP-001', 'Budi', 'budi@example.com', '0812', 'COMP', 'SITE-A', 'HSE', 'OFFICER', 'true'],
        ]),
    ])->assertRedirect();

    $this->assertDatabaseHas('employees', [
        'employee_no' => 'EMP-001',
        'company_id' => $company->id,
        'site_id' => $site->id,
        'department_id' => $department->id,
        'position_id' => $position->id,
    ]);
});

test('csv template is permission gated and has expected headers', function () {
    actingAs($this->admin);
    $response = $this->get(route('admin.import.template', 'employees'));

    $response->assertOk()->assertHeader('content-type', 'text/csv; charset=UTF-8');
    expect($response->streamedContent())->toStartWith('employee_no,name,email');
});
