<?php

namespace Database\Factories\Modules\Training;

use App\Models\Modules\Training\TrainingProgram;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TrainingProgram>
 */
class TrainingProgramFactory extends Factory
{
    protected $model = TrainingProgram::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $categories = ['safety', 'technical', 'compliance', 'soft_skill', 'environment', 'security', 'quality', 'first_aid'];
        $isCertification = fake()->boolean(60);

        return [
            'code' => fake()->unique()->bothify('TRN-###'),
            'name' => fake()->randomElement([
                'HSE Induction',
                'Fire Safety Training',
                'Forklift Operation',
                'Confined Space Entry',
                'Working at Height',
                'First Aid & CPR',
                'Chemical Handling',
                'Emergency Response',
                'Lock Out Tag Out',
                'Scaffolding Safety',
            ]),
            'description' => fake()->paragraph(3),
            'category' => fake()->randomElement($categories),
            'duration_hours' => fake()->randomElement([4, 8, 16, 24, 40]),
            'is_certification' => $isCertification,
            'validity_months' => $isCertification ? fake()->randomElement([12, 24, 36]) : null,
            'is_active' => true,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    public function certification(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_certification' => true,
            'validity_months' => 24,
        ]);
    }

    public function noCertification(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_certification' => false,
            'validity_months' => null,
        ]);
    }
}
