<?php

namespace Database\Factories\Modules\Audit;

use App\Models\Core\MasterData\Department;
use App\Models\Modules\Audit\Audit;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Audit>
 */
class AuditFactory extends Factory
{
    protected $model = Audit::class;

    public function definition(): array
    {
        return [
            'audit_number' => 'AUD-' . now()->year . '-' . str_pad((string) fake()->unique()->numberBetween(1, 9999), 4, '0', STR_PAD_LEFT),
            'title' => fake()->sentence(6),
            'audit_type' => fake()->randomElement(['internal', 'external', 'supplier', 'regulatory']),
            'scope' => fake()->paragraph(2),
            'department_id' => Department::factory(),
            'lead_auditor_id' => User::factory(),
            'scheduled_date' => fake()->dateTimeBetween('now', '+1 month'),
            'start_date' => null,
            'end_date' => null,
            'report_date' => null,
            'close_date' => null,
            'status' => 'planned',
            'summary' => null,
            'created_by' => User::factory(),
        ];
    }

    public function planned(): static
    {
        return $this->state(fn (array $attributes) => ['status' => 'planned']);
    }

    public function inProgress(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'in_progress',
            'start_date' => now()->subDays(2),
        ]);
    }

    public function reportReady(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'report_ready',
            'start_date' => now()->subDays(5),
            'report_date' => now()->subDay(),
            'summary' => fake()->paragraph(3),
        ]);
    }

    public function closed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'closed',
            'start_date' => now()->subDays(10),
            'report_date' => now()->subDays(3),
            'close_date' => now()->subDay(),
            'summary' => fake()->paragraph(3),
        ]);
    }
}
