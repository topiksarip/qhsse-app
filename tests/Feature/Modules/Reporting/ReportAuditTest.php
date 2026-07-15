<?php

namespace Tests\Feature\Modules\Reporting;

use App\Models\Modules\Reporting\ReportTemplate;
use App\Models\Modules\Reporting\SavedReport;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use function Pest\Laravel\actingAs;
use Tests\TestCase;

uses(RefreshDatabase::class);

beforeEach(function () {
    Artisan::call('db:seed', ['--class' => 'RolesAndPermissionsSeeder']);
    Artisan::call('db:seed', ['--class' => 'QhsseMasterDataSeeder']);
});

test('M19 WS-1: creating a report template writes an audit log', function (): void {
    $user = User::factory()->create();
    $user->assignRole('Super Admin');

    actingAs($user);
    $this->post(route('report-templates.store'), [
        'name' => 'Monthly Safety Report',
        'type' => 'custom',
        'description' => 'Test template',
        'config' => ['sections' => []],
        'is_active' => true,
        'is_predefined' => false,
    ])->assertRedirect();

    $template = ReportTemplate::where('name', 'Monthly Safety Report')->latest()->first();
    expect($template)->not->toBeNull();

    $this->assertDatabaseHas('audit_logs', [
        'event' => 'created',
        'module_name' => 'reporting',
        'reference_id' => $template->id,
        'actor_id' => $user->id,
    ]);
});

test('M19 WS-1: updating a report template writes an audit log with changes', function (): void {
    $user = User::factory()->create();
    $user->assignRole('Super Admin');

    $template = ReportTemplate::factory()->create(['name' => 'Original Name', 'is_active' => true]);

    actingAs($user);
    $this->put(route('report-templates.update', $template), [
        'name' => 'Updated Name',
        'type' => 'custom',
        'description' => 'Updated',
        'config' => ['sections' => []],
        'is_active' => true,
        'is_predefined' => false,
    ])->assertRedirect();

    $this->assertDatabaseHas('audit_logs', [
        'event' => 'updated',
        'module_name' => 'reporting',
        'reference_id' => $template->id,
        'actor_id' => $user->id,
    ]);
});

test('M19 WS-1: toggling template active writes an audit log', function (): void {
    $user = User::factory()->create();
    $user->assignRole('Super Admin');

    $template = ReportTemplate::factory()->create(['is_active' => true]);

    actingAs($user);
    $this->post(route('report-templates.toggle-active', $template))
        ->assertRedirect();

    $this->assertDatabaseHas('audit_logs', [
        'event' => 'updated',
        'module_name' => 'reporting',
        'reference_id' => $template->id,
    ]);
});

test('M19 WS-1: deleting a report template writes an audit log', function (): void {
    $user = User::factory()->create();
    $user->assignRole('Super Admin');

    $template = ReportTemplate::factory()->create(['is_predefined' => false, 'is_active' => true]);

    actingAs($user);
    $this->delete(route('report-templates.destroy', $template))
        ->assertRedirect();

    $this->assertDatabaseHas('audit_logs', [
        'event' => 'deleted',
        'module_name' => 'reporting',
        'reference_id' => $template->id,
        'actor_id' => $user->id,
    ]);
});

test('M19 WS-1: generating a saved report writes an audit log', function (): void {
    $user = User::factory()->create();
    $user->assignRole('Super Admin');

    $template = ReportTemplate::factory()->create(['is_active' => true]);

    actingAs($user);
    $this->post(route('saved-reports.store'), [
        'name' => 'Generated Report',
        'template_id' => $template->id,
        'format' => 'csv',
        'date_from' => now()->subDays(7)->format('Y-m-d'),
        'date_to' => now()->format('Y-m-d'),
        'parameters' => [],
    ])->assertRedirect();

    $report = SavedReport::where('name', 'Generated Report')->latest()->first();
    expect($report)->not->toBeNull();

    $this->assertDatabaseHas('audit_logs', [
        'event' => 'created',
        'module_name' => 'reporting',
        'reference_id' => $report->id,
        'actor_id' => $user->id,
    ]);
});

test('M19 WS-1: deleting a saved report writes an audit log', function (): void {
    $user = User::factory()->create();
    $user->assignRole('Super Admin');

    $report = SavedReport::factory()->create(['status' => 'completed', 'file_path' => null]);

    actingAs($user);
    $this->delete(route('saved-reports.destroy', $report))
        ->assertRedirect();

    $this->assertDatabaseHas('audit_logs', [
        'event' => 'deleted',
        'module_name' => 'reporting',
        'reference_id' => $report->id,
        'actor_id' => $user->id,
    ]);
});
