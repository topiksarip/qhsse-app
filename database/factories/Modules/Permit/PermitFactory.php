<?php

namespace Database\Factories\Modules\Permit;

use App\Models\Core\MasterData\Site;
use App\Models\Modules\Permit\Permit;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Permit>
 */
class PermitFactory extends Factory
{
    protected $model = Permit::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $startDatetime = $this->faker->dateTimeBetween('-1 month', '+2 weeks');
        $validityHours = $this->faker->randomElement([8, 12, 24, 48, 72]);
        $endDatetime = (clone $startDatetime)->modify("+{$validityHours} hours");

        return [
            'permit_number' => 'PTW-'.now()->year.'-'.str_pad((string) $this->faker->unique()->numberBetween(1, 9999), 4, '0', STR_PAD_LEFT),
            'type' => $this->faker->randomElement(['hot_work', 'working_at_height', 'confined_space', 'electrical', 'excavation', 'lifting', 'other']),
            'title' => $this->faker->sentence(5),
            'description' => $this->faker->paragraph(3),
            'site_id' => Site::factory(),
            'area_id' => null,
            'department_id' => null,
            'contractor_id' => null,
            'work_location' => $this->faker->randomElement(['Building A', 'Warehouse 2', 'Production Floor', 'Outdoor Area', 'Maintenance Shop']),
            'work_description' => $this->faker->paragraph(5),
            'start_datetime' => $startDatetime,
            'end_datetime' => $endDatetime,
            'validity_hours' => $validityHours,
            'status' => 'draft',
            'risk_level' => $this->faker->randomElement(['low', 'medium', 'high']),
            'jsa_reference' => $this->faker->optional(0.3)->bothify('JSA-####'),
            'approved_by' => null,
            'approved_at' => null,
            'closed_by' => null,
            'closed_at' => null,
            'cancellation_reason' => null,
            'created_by' => User::factory(),
        ];
    }

    public function submitted(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'submitted',
        ]);
    }

    public function approved(): static
    {
        return $this->state(function (array $attributes) {
            $approver = User::factory()->create();

            return [
                'status' => 'approved',
                'approved_by' => $approver->id,
                'approved_at' => now()->subHours(2),
            ];
        });
    }

    public function active(): static
    {
        return $this->state(function (array $attributes) {
            $approver = User::factory()->create();

            return [
                'status' => 'active',
                'approved_by' => $approver->id,
                'approved_at' => now()->subHours(4),
                'start_datetime' => now()->subHour(),
                'end_datetime' => now()->addHours(7),
                'validity_hours' => 8,
            ];
        });
    }

    public function expired(): static
    {
        return $this->state(function (array $attributes) {
            $approver = User::factory()->create();

            return [
                'status' => 'active',
                'approved_by' => $approver->id,
                'approved_at' => now()->subDays(3),
                'start_datetime' => now()->subDays(2),
                'end_datetime' => now()->subHours(2),
                'validity_hours' => 24,
            ];
        });
    }
}
