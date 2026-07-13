<?php

declare(strict_types=1);

namespace Database\Factories\Modules\EmergencyPreparedness;

use App\Models\Core\MasterData\Site;
use App\Models\Modules\EmergencyPreparedness\EmergencyDrill;
use App\Models\Modules\EmergencyPreparedness\EmergencyPlan;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class EmergencyDrillFactory extends Factory
{
    protected $model = EmergencyDrill::class;

    public function definition(): array
    {
        return [
            'drill_number' => 'EMG-'.date('Y').'-'.str_pad((string) $this->faker->unique()->numberBetween(1, 9999), 4, '0', STR_PAD_LEFT),
            'emergency_plan_id' => EmergencyPlan::factory(),
            'scheduled_date' => $this->faker->dateTimeBetween('-30 days', '+90 days'),
            'executed_date' => null,
            'site_id' => Site::factory(),
            'participants_count' => null,
            'observer_id' => User::factory(),
            'result' => null,
            'findings' => null,
            'recommendations' => null,
            'status' => 'scheduled',
        ];
    }

    public function scheduled(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'scheduled',
            'executed_date' => null,
            'result' => null,
            'findings' => null,
            'recommendations' => null,
            'participants_count' => null,
        ]);
    }

    public function executed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'executed',
            'executed_date' => now()->subDays($this->faker->numberBetween(1, 30)),
            'result' => $this->faker->randomElement(['pass', 'fail', 'needs_improvement']),
            'findings' => $this->faker->paragraph(3),
            'recommendations' => $this->faker->paragraph(2),
            'participants_count' => $this->faker->numberBetween(10, 100),
        ]);
    }

    public function passed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'executed',
            'executed_date' => now()->subDays($this->faker->numberBetween(1, 30)),
            'result' => 'pass',
            'findings' => $this->faker->paragraph(3),
            'recommendations' => $this->faker->paragraph(2),
            'participants_count' => $this->faker->numberBetween(10, 100),
        ]);
    }

    public function failed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'executed',
            'executed_date' => now()->subDays($this->faker->numberBetween(1, 30)),
            'result' => 'fail',
            'findings' => $this->faker->paragraph(3),
            'recommendations' => $this->faker->paragraph(2),
            'participants_count' => $this->faker->numberBetween(10, 100),
        ]);
    }

    public function needsImprovement(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'executed',
            'executed_date' => now()->subDays($this->faker->numberBetween(1, 30)),
            'result' => 'needs_improvement',
            'findings' => $this->faker->paragraph(3),
            'recommendations' => $this->faker->paragraph(2),
            'participants_count' => $this->faker->numberBetween(10, 100),
        ]);
    }

    public function upcoming(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'scheduled',
            'scheduled_date' => now()->addDays($this->faker->numberBetween(1, 30)),
        ]);
    }

    public function overdue(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'scheduled',
            'scheduled_date' => now()->subDays($this->faker->numberBetween(1, 30)),
        ]);
    }
}
