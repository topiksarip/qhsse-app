<?php

namespace Database\Factories\Modules\Security;

use App\Models\Core\MasterData\Site;
use App\Models\Modules\Security\PatrolChecklist;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PatrolChecklist>
 */
class PatrolChecklistFactory extends Factory
{
    protected $model = PatrolChecklist::class;

    public function definition(): array
    {
        $status = fake()->randomElement(['scheduled', 'in_progress', 'completed']);
        $scheduledAt = match ($status) {
            'completed' => fake()->dateTimeBetween('-7 days', '-3 hours'),
            'in_progress' => fake()->dateTimeBetween('-7 days', '-1 hour'),
            default => fake()->dateTimeBetween('-7 days', '+7 days'),
        };

        $base = [
            'patrol_number' => 'SPL-'.now()->year.'-'.str_pad((string) fake()->unique()->numberBetween(1, 9999), 4, '0', STR_PAD_LEFT),
            'title' => fake()->randomElement(['Night Patrol', 'Morning Patrol', 'Perimeter Check', 'Gate Inspection']),
            'description' => fake()->optional()->sentence(),
            'site_id' => Site::factory(),
            'area_id' => null,
            'scheduled_at' => $scheduledAt,
            'assigned_to' => User::factory(),
            'status' => $status,
            'started_at' => null,
            'completed_at' => null,
            'completed_by' => null,
            'notes' => fake()->optional()->sentence(),
        ];

        if ($status === 'in_progress') {
            $base['started_at'] = fake()->dateTimeBetween($scheduledAt, 'now');
        }

        if ($status === 'completed') {
            $startedAt = fake()->dateTimeBetween($scheduledAt, '-1 hour');
            $base['started_at'] = $startedAt;
            $base['completed_at'] = fake()->dateTimeBetween($startedAt, 'now');
            $base['completed_by'] = User::factory();
        }

        return $base;
    }

    public function scheduled(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'scheduled',
            'started_at' => null,
            'completed_at' => null,
            'completed_by' => null,
        ]);
    }

    public function inProgress(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'in_progress',
            'started_at' => fake()->dateTimeBetween($attributes['scheduled_at'] ?? '-1 hour', 'now'),
            'completed_at' => null,
            'completed_by' => null,
        ]);
    }

    public function completed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'completed',
            'started_at' => fake()->dateTimeBetween($attributes['scheduled_at'] ?? '-2 hours', '-1 hour'),
            'completed_at' => fake()->dateTimeBetween('-1 hour', 'now'),
            'completed_by' => User::factory(),
        ]);
    }
}
