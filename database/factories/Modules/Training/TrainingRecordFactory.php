<?php

namespace Database\Factories\Modules\Training;

use App\Models\Core\Users\Employee;
use App\Models\Modules\Training\TrainingProgram;
use App\Models\Modules\Training\TrainingRecord;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TrainingRecord>
 */
class TrainingRecordFactory extends Factory
{
    protected $model = TrainingRecord::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $startDate = fake()->dateTimeBetween('-6 months', '+1 month');
        $endDate = fake()->optional(0.8)->dateTimeBetween($startDate, '+7 days');

        return [
            'training_number' => 'TRN-'.now()->year.'-'.str_pad((string) fake()->unique()->numberBetween(1, 9999), 4, '0', STR_PAD_LEFT),
            'employee_id' => Employee::factory(),
            'training_program_id' => TrainingProgram::factory(),
            'provider' => fake()->optional(0.6)->company(),
            'start_date' => $startDate,
            'end_date' => $endDate,
            'status' => fake()->randomElement(['scheduled', 'in_progress', 'completed']),
            'score' => fake()->optional(0.5)->randomFloat(2, 60, 100),
            'result' => fake()->optional(0.5)->randomElement(['pass', 'fail', 'pending']),
            'certificate_number' => fake()->optional(0.4)->bothify('CERT-####-????'),
            'certificate_file_id' => null,
            'expiry_date' => null,
            'notes' => fake()->optional(0.3)->sentence(10),
        ];
    }

    public function completed(): static
    {
        return $this->state(function (array $attributes) {
            $endDate = now()->subMonths(2);
            $program = TrainingProgram::find($attributes['training_program_id']) ?? TrainingProgram::factory()->certification()->create();

            return [
                'status' => 'completed',
                'start_date' => $endDate->copy()->subDays(3),
                'end_date' => $endDate,
                'score' => fake()->randomFloat(2, 75, 100),
                'result' => 'pass',
                'expiry_date' => $program->validity_months ? $endDate->copy()->addMonths($program->validity_months) : null,
            ];
        });
    }

    public function expired(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'expired',
            'end_date' => now()->subYears(3),
            'expiry_date' => now()->subMonths(6),
            'score' => 85.00,
            'result' => 'pass',
        ]);
    }

    public function scheduled(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'scheduled',
            'start_date' => now()->addWeek(),
            'end_date' => null,
            'score' => null,
            'result' => null,
            'expiry_date' => null,
        ]);
    }
}
