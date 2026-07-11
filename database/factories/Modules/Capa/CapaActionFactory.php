<?php

namespace Database\Factories\Modules\Capa;

use App\Models\Core\MasterData\Priority;
use App\Models\Core\MasterData\Site;
use App\Models\Modules\Capa\CapaAction;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<CapaAction> */
class CapaActionFactory extends Factory
{
    public function definition(): array
    {
        $assigner = User::factory()->create();
        return [
            'action_number' => 'ACT-' . now()->year . '-' . str_pad((string) fake()->numberBetween(1, 9999), 4, '0', STR_PAD_LEFT),
            'title' => fake()->sentence(5),
            'description' => fake()->paragraph(3),
            'source_module' => fake()->randomElement(['incident', 'inspection', 'audit', 'manual']),
            'source_reference_id' => null,
            'source_type' => fake()->randomElement(['corrective', 'preventive']),
            'site_id' => Site::factory(),
            'department_id' => null,
            'assigned_to' => User::factory(),
            'assigned_by' => $assigner->id,
            'assigned_at' => now(),
            'due_date' => fake()->optional(0.7)->dateTimeBetween('+1 week', '+1 month'),
            'severity_id' => null,
            'priority_id' => Priority::factory(),
            'status' => 'open',
            'verification_note' => null,
            'verified_by' => null,
            'verified_at' => null,
            'closed_at' => null,
        ];
    }
}
