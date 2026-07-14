<?php

namespace Tests\Feature\Modules\Incident;

use App\Models\Modules\Incident\IncidentReport;
use App\Models\User;
use Database\Factories\Modules\Incident\IncidentReportFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class IncidentReportTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);
        Role::findOrCreate('Super Admin');
    }

    public function test_index_requires_permission(): void
    {
        $user = User::factory()->create();
        $response = $this->actingAs($user)->get(route('modules.incident.index'));
        $response->assertForbidden();
    }

    public function test_user_with_permission_can_list(): void
    {
        $user = User::factory()->create();
        $user->assignRole('Super Admin');
        IncidentReportFactory::new()->count(3)->create();
        $response = $this->actingAs($user)->get(route('modules.incident.index'));
        $response->assertOk();
        $response->assertInertia(fn ($p) => $p->component('Modules/Incident/Index')
            ->has('items'));
    }

    public function test_store_creates_record_with_number(): void
    {
        $user = User::factory()->create();
        $user->assignRole('Super Admin');
        $response = $this->actingAs($user)->post(route('modules.incident.store'), [
            'title' => 'Test incident',
            'description' => 'desc',
            'status' => 'draft',
        ]);
        $response->assertRedirect(route('modules.incident.index'));
        $this->assertDatabaseHas('02_incident_reporting', ['title' => 'Test incident']);
        $this->assertNotNull(IncidentReport::first()->number);
    }

    public function test_workflow_submit_then_approve(): void
    {
        $user = User::factory()->create();
        $user->assignRole('Super Admin');
        $report = IncidentReportFactory::new()->draft()->create();
        $this->actingAs($user)->post(route('modules.incident.submit', $report))->assertRedirect();
        $this->assertEquals('submitted', $report->fresh()->status);
        $this->actingAs($user)->post(route('modules.incident.review', $report))->assertRedirect();
        $this->actingAs($user)->post(route('modules.incident.approve', $report))->assertRedirect();
        $this->assertEquals('approved', $report->fresh()->status);
    }

    public function test_reject_requires_reason(): void
    {
        $user = User::factory()->create();
        $user->assignRole('Super Admin');
        $report = IncidentReportFactory::new()->create(['status' => 'submitted']);
        $this->actingAs($user)->post(route('modules.incident.reject', $report), [])
            ->assertSessionHasErrors('reason');
    }
}
