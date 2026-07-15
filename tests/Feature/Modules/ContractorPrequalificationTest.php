<?php

use App\Models\Modules\Contractor\Contractor;
use App\Models\Modules\Contractor\ContractorEvaluation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed([
        \Database\Seeders\RolesAndPermissionsSeeder::class,
        \Database\Seeders\NumberingFormatSeeder::class,
        \Database\Seeders\NotificationTemplateSeeder::class,
    ]);
});

function contractorActor(string $role): User
{
    $user = User::factory()->create();
    $user->assignRole($role);
    return $user;
}

it('allows QHSSE Officer to set prequalification', function () {
    Notification::fake();
    $actor = contractorActor('QHSSE Officer');
    $contractor = Contractor::factory()->create(['is_prequalified' => false]);

    $response = $this->actingAs($actor)->post(route('contractors.prequalify', $contractor), [
        'prequalified_until' => now()->addYear()->format('Y-m-d'),
    ]);

    $response->assertRedirect();
    $contractor->refresh();
    expect($contractor->is_prequalified)->toBeTrue()
        ->and($contractor->prequalified_until)->not->toBeNull();
});

it('allows revoking prequalification', function () {
    Notification::fake();
    $actor = contractorActor('QHSSE Officer');
    $contractor = Contractor::factory()->create([
        'is_prequalified' => true,
        'prequalified_until' => now()->addYear(),
    ]);

    $response = $this->actingAs($actor)->delete(route('contractors.prequalify.revoke', $contractor));

    $response->assertRedirect();
    $contractor->refresh();
    expect($contractor->is_prequalified)->toBeFalse()
        ->and($contractor->prequalified_until)->toBeNull();
});

it('blocks reporter from prequalifying (permission gate)', function () {
    $actor = contractorActor('Employee / Reporter');
    $contractor = Contractor::factory()->create(['is_prequalified' => false]);

    $response = $this->actingAs($actor)->post(route('contractors.prequalify', $contractor), [
        'prequalified_until' => now()->addYear()->format('Y-m-d'),
    ]);

    $response->assertForbidden();
});

it('rejects revoking when not prequalified', function () {
    $actor = contractorActor('QHSSE Officer');
    $contractor = Contractor::factory()->create([
        'is_prequalified' => false,
        'prequalified_until' => null,
    ]);

    $response = $this->actingAs($actor)->delete(route('contractors.prequalify.revoke', $contractor));

    $response->assertRedirect();
    $response->assertSessionHasErrors('prequalify');
});

it('stores an evaluation and recalculates safety rating', function () {
    Notification::fake();
    $actor = contractorActor('QHSSE Officer');
    $contractor = Contractor::factory()->create(['safety_rating' => null]);

    $criteria = [
        'safety_management' => 20,
        'training' => 18,
        'incident_history' => 19,
        'compliance' => 17,
        'performance' => 16,
    ]; // total 90 -> pass

    $response = $this->actingAs($actor)->post(route('contractors.evaluations.store', $contractor), [
        'evaluation_date' => now()->subDays(5)->format('Y-m-d'),
        'criteria' => $criteria,
        'notes' => 'Good performance',
    ]);

    $response->assertRedirect();
    expect(ContractorEvaluation::count())->toBe(1);

    $evaluation = ContractorEvaluation::first();
    expect($evaluation->total_score)->toBe(90)
        ->and($evaluation->result)->toBe('pass')
        ->and($evaluation->evaluator_id)->toBe($actor->id);

    $contractor->refresh();
    expect($contractor->safety_rating)->toBe('excellent'); // avg 90 -> >=85
});

it('derives conditional result for mid score', function () {
    $actor = contractorActor('QHSSE Officer');
    $contractor = Contractor::factory()->create();

    $criteria = [
        'safety_management' => 13,
        'training' => 12,
        'incident_history' => 13,
        'compliance' => 12,
        'performance' => 12,
    ]; // total 62 -> conditional

    $this->actingAs($actor)->post(route('contractors.evaluations.store', $contractor), [
        'evaluation_date' => now()->subDays(3)->format('Y-m-d'),
        'criteria' => $criteria,
    ]);

    $evaluation = ContractorEvaluation::first();
    expect($evaluation->total_score)->toBe(62)
        ->and($evaluation->result)->toBe('conditional');
    $contractor->refresh();
    expect($contractor->safety_rating)->toBe('fair'); // avg 62 -> >=55 fair
});

it('blocks reporter from evaluating (permission gate)', function () {
    $actor = contractorActor('Employee / Reporter');
    $contractor = Contractor::factory()->create();

    $response = $this->actingAs($actor)->post(route('contractors.evaluations.store', $contractor), [
        'evaluation_date' => now()->format('Y-m-d'),
        'criteria' => ['a' => 10],
    ]);

    $response->assertForbidden();
});
