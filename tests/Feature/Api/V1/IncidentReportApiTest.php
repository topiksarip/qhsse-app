<?php

use App\Models\Modules\Incident\IncidentReport;
use App\Models\User;
use Database\Factories\Modules\Incident\IncidentReportFactory;
use Database\Seeders\NumberingFormatSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Database\Seeders\WorkflowSeeder;
use Illuminate\Support\Facades\Hash;
use function Pest\Laravel\seed;

beforeEach(function (): void {
    seed([RolesAndPermissionsSeeder::class, NumberingFormatSeeder::class, WorkflowSeeder::class]);
});

function apiUser(string $role, array $attrs = []): User
{
    $user = User::factory()->create(array_merge([
        'is_active' => true,
        'password' => Hash::make('secret123'),
    ], $attrs));
    $user->assignRole($role);

    return $user;
}

function loginToken(User $user): string
{
    return $user->createToken('test-device')->plainTextToken;
}

it('rejects unauthenticated requests with 401', function (): void {
    $this->getJson('/api/v1/incidents')->assertStatus(401);
});

it('logs in and returns a token with permissions', function (): void {
    $user = apiUser('QHSSE Officer', ['email' => 'officer@test.com']);
    $resp = $this->postJson('/api/v1/auth/login', [
        'email' => 'officer@test.com',
        'password' => 'secret123',
        'device_name' => 'flutter-test',
    ]);

    $resp->assertOk()
        ->assertJsonStructure(['data' => ['token', 'user' => ['id', 'roles', 'permissions']], 'message'])
        ->assertJsonPath('data.user.permissions', fn ($perms) => is_array($perms) && in_array('incident.reports.view', $perms));
});

it('blocks inactive users from logging in', function (): void {
    apiUser('QHSSE Officer', ['email' => 'inactive@test.com', 'is_active' => false]);
    $this->postJson('/api/v1/auth/login', ['email' => 'inactive@test.com', 'password' => 'secret123'])
        ->assertStatus(422);
});

it('lists incidents for authenticated user with meta pagination', function (): void {
    $user = apiUser('QHSSE Manager');
    IncidentReport::factory()->count(3)->create(['reporter_id' => $user->id]);

    $resp = $this->withHeader('Authorization', 'Bearer '.loginToken($user))
        ->getJson('/api/v1/incidents');

    $resp->assertOk()
        ->assertJsonStructure(['data' => [['id', 'incident_number', 'status']], 'meta' => ['current_page', 'last_page', 'total']]);
});

it('creates an incident via JSON and assigns a generated number', function (): void {
    $user = apiUser('Employee / Reporter');
    $site = \App\Models\Core\MasterData\Site::factory()->create(['is_active' => true]);
    // Link the reporter's employee to the site so ensureSiteAllowed passes.
    $employee = \App\Models\Core\Users\Employee::factory()->create(['site_id' => $site->id]);
    $user->update(['employee_id' => $employee->id]);
    $severity = \App\Models\Core\MasterData\Severity::factory()->create();
    $priority = \App\Models\Core\MasterData\Priority::factory()->create();

    $payload = [
        'title' => 'Near miss forklift',
        'category' => 'near_miss',
        'occurred_at' => now()->subHour()->toDateTimeString(),
        'site_id' => $site->id,
        'severity_id' => $severity->id,
        'priority_id' => $priority->id,
        'description' => 'Forklift hampir menabrak pejalan kaki.',
        'action' => 'submit',
    ];

    $resp = $this->withHeader('Authorization', 'Bearer '.loginToken($user))
        ->postJson('/api/v1/incidents', $payload);

    $resp->assertCreated()
        ->assertJsonPath('data.status', 'submitted')
        ->assertJsonPath('message', 'Laporan insiden berhasil dibuat.');
    expect($resp->json('data.incident_number'))->toStartWith('INC-');

    $this->assertDatabaseHas('incidents', ['incident_number' => $resp->json('data.incident_number'), 'status' => 'submitted']);
});

it('returns 422 on invalid incident payload', function (): void {
    $user = apiUser('Employee / Reporter');
    $this->withHeader('Authorization', 'Bearer '.loginToken($user))
        ->postJson('/api/v1/incidents', ['title' => ''])
        ->assertStatus(422);
});

it('shows, updates and deletes a draft incident', function (): void {
    $user = apiUser('QHSSE Manager');
    $token = loginToken($user);
    $headers = ['Authorization' => 'Bearer '.$token];
    $incident = IncidentReport::factory()->create(['reporter_id' => $user->id, 'status' => 'draft']);

    $this->withHeaders($headers)->getJson("/api/v1/incidents/{$incident->id}")
        ->assertOk()->assertJsonPath('data.id', $incident->id);

    $this->withHeaders($headers)
        ->putJson("/api/v1/incidents/{$incident->id}", ['title' => 'Updated title', 'description' => 'x'])
        ->assertOk()->assertJsonPath('data.title', 'Updated title');

    $incident->refresh();
    expect($incident->title)->toBe('Updated title');

    $this->withHeaders($headers)->deleteJson("/api/v1/incidents/{$incident->id}")->assertOk();
    $this->assertDatabaseMissing('incidents', ['id' => $incident->id]);
});

it('blocks a user without delete permission from destroying', function (): void {
    $user = apiUser('Employee / Reporter');
    $incident = IncidentReport::factory()->create(['reporter_id' => $user->id, 'status' => 'draft']);

    $this->withHeader('Authorization', 'Bearer '.loginToken($user))
        ->deleteJson("/api/v1/incidents/{$incident->id}")
        ->assertForbidden();
});

it('transitions an incident submit then review via API', function (): void {
    $manager = apiUser('QHSSE Manager');
    $officer = apiUser('QHSSE Officer');

    $incident = IncidentReport::factory()->create(['reporter_id' => $manager->id, 'status' => 'draft']);
    // Officer needs to see the incident: link their employee to the incident's site.
    $officerEmployee = \App\Models\Core\Users\Employee::factory()->create(['site_id' => $incident->site_id]);
    $officer->update(['employee_id' => $officerEmployee->id]);
    $token = loginToken($officer);
    $headers = ['Authorization' => 'Bearer '.$token];

    // submit: draft -> submitted
    $this->withHeaders($headers)->postJson("/api/v1/incidents/{$incident->id}/submit")->assertOk();
    expect(IncidentReport::find($incident->id)->status)->toBe('submitted');

    // review: submitted -> under_review
    $this->withHeaders($headers)->postJson("/api/v1/incidents/{$incident->id}/review")->assertOk();
    expect(IncidentReport::find($incident->id)->status)->toBe('under_review');
});

it('closes an incident from action_open status via API', function (): void {
    $officer = apiUser('QHSSE Officer');
    $incident = IncidentReport::factory()->create(['reporter_id' => $officer->id, 'status' => 'draft']);
    $officerEmployee = \App\Models\Core\Users\Employee::factory()->create(['site_id' => $incident->site_id]);
    $officer->update(['employee_id' => $officerEmployee->id]);

    // Drive the workflow instance to `action_open` (valid close source) via the
    // same lifecycle service the API uses, since the API only exposes
    // submit/review/close transition endpoints.
    $lifecycle = app(\App\Modules\Incident\IncidentLifecycle::class);
    $lifecycle->transition($incident, $officer, 'submit', 'submitted');
    $lifecycle->transition($incident, $officer, 'review', 'under_review');
    $lifecycle->transition($incident, $officer, 'open_action', 'action_open');
    expect(IncidentReport::find($incident->id)->status)->toBe('action_open');

    $this->withHeader('Authorization', 'Bearer '.loginToken($officer))
        ->postJson("/api/v1/incidents/{$incident->id}/close", ['reason' => 'Resolved and verified.'])
        ->assertOk();

    expect(IncidentReport::find($incident->id)->status)->toBe('closed');
});

it('honors scope: a department-scoped reporter only sees own incidents', function (): void {
    $reporter = apiUser('Employee / Reporter');
    $other = apiUser('Employee / Reporter');
    IncidentReport::factory()->create(['reporter_id' => $reporter->id]);
    IncidentReport::factory()->create(['reporter_id' => $other->id]);

    $resp = $this->withHeader('Authorization', 'Bearer '.loginToken($reporter))
        ->getJson('/api/v1/incidents');

    $resp->assertOk();
    expect($resp->json('meta.total'))->toBe(1);
});
