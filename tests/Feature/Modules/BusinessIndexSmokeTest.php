<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed();
    $this->admin = User::where('email', 'test@example.com')->firstOrFail();
});

$businessIndexRoutes = [
    'dashboard',
    'incident.reports.index',
    'investigation.reports.index',
    'capa.actions.index',
    'inspection.templates.index',
    'inspection.checklists.index',
    'audits.index',
    'document.control.index',
    'training.programs.index',
    'training.records.index',
    'training.matrix.index',
    'permit.work.index',
    'environment.records.index',
    'security.incidents.index',
    'emergency.plans.index',
    'emergency.drills.index',
    'emergency.contacts.index',
    'contractors.index',
    'assets.index',
    'campaigns.index',
    'report-templates.index',
    'saved-reports.index',
    'quality.ncrs.index',
    'risk.registers.index',
    'legal.registers.index',
];

foreach ($businessIndexRoutes as $routeName) {
    test("super admin can render {$routeName}", function () use ($routeName) {
        $this->actingAs($this->admin)
            ->get(route($routeName))
            ->assertOk();
    });
}
