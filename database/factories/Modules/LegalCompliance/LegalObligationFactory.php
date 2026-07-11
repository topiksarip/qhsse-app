<?php

declare(strict_types=1);

namespace Database\Factories\Modules\LegalCompliance;

use App\Models\Core\ManagedFile;
use App\Models\Modules\LegalCompliance\LegalObligation;
use App\Models\Modules\LegalCompliance\LegalRegister;
use Illuminate\Database\Eloquent\Factories\Factory;

class LegalObligationFactory extends Factory
{
    protected $model = LegalObligation::class;

    public function definition(): array
    {
        $frequencies = ['monthly', 'quarterly', 'annual'];
        $frequency = fake()->randomElement($frequencies);
        
        return [
            'legal_register_id' => LegalRegister::factory(),
            'obligation_description' => fake()->sentence(12),
            'frequency' => $frequency,
            'last_completed' => fake()->optional(0.6)->dateTimeBetween('-3 months', 'now'),
            'next_due' => fake()->optional(0.8)->dateTimeBetween('-30 days', '+90 days'),
            'evidence_file_id' => fake()->optional(0.4)->randomElement([null, ManagedFile::factory()]),
            'status' => fake()->randomElement(['pending', 'completed']),
        ];
    }

    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'pending',
            'last_completed' => null,
            'evidence_file_id' => null,
        ]);
    }

    public function completed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'completed',
            'last_completed' => now()->subDays(5)->toDateString(),
            'evidence_file_id' => ManagedFile::factory(),
        ]);
    }

    public function overdue(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'pending',
            'next_due' => now()->subDays(10)->toDateString(),
            'evidence_file_id' => null,
        ]);
    }

    public function dueSoon(int $days = 7): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'pending',
            'next_due' => now()->addDays($days)->toDateString(),
            'evidence_file_id' => null,
        ]);
    }

    public function monthly(): static
    {
        return $this->state(fn (array $attributes) => [
            'frequency' => 'monthly',
        ]);
    }

    public function quarterly(): static
    {
        return $this->state(fn (array $attributes) => [
            'frequency' => 'quarterly',
        ]);
    }

    public function annual(): static
    {
        return $this->state(fn (array $attributes) => [
            'frequency' => 'annual',
        ]);
    }
}
