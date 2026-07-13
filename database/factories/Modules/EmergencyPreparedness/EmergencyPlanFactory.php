<?php

declare(strict_types=1);

namespace Database\Factories\Modules\EmergencyPreparedness;

use App\Models\Core\MasterData\Site;
use App\Models\Modules\EmergencyPreparedness\EmergencyPlan;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class EmergencyPlanFactory extends Factory
{
    protected $model = EmergencyPlan::class;

    public function definition(): array
    {
        $types = ['fire', 'medical', 'spill', 'evacuation', 'natural_disaster', 'security', 'other'];

        return [
            'plan_number' => 'EMG-'.date('Y').'-'.str_pad((string) $this->faker->unique()->numberBetween(1, 9999), 4, '0', STR_PAD_LEFT),
            'name' => $this->faker->sentence(4),
            'type' => $this->faker->randomElement($types),
            'site_id' => Site::factory(),
            'description' => $this->faker->paragraph(3),
            'response_procedure' => $this->faker->paragraphs(5, true),
            'escalation_procedure' => $this->faker->paragraphs(3, true),
            'contact_person_id' => User::factory(),
            'emergency_contacts' => $this->faker->optional(0.6)->passthrough([
                [
                    'name' => $this->faker->name(),
                    'role' => $this->faker->jobTitle(),
                    'phone' => $this->faker->phoneNumber(),
                ],
                [
                    'name' => $this->faker->name(),
                    'role' => $this->faker->jobTitle(),
                    'phone' => $this->faker->phoneNumber(),
                ],
            ]),
            'equipment_needed' => $this->faker->optional(0.7)->paragraph(2),
        ];
    }

    public function fire(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'fire',
            'name' => 'Rencana Tanggap Darurat Kebakaran',
        ]);
    }

    public function medical(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'medical',
            'name' => 'Rencana Tanggap Darurat Medis',
        ]);
    }

    public function evacuation(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'evacuation',
            'name' => 'Rencana Evakuasi Darurat',
        ]);
    }

    public function spill(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'spill',
            'name' => 'Rencana Tanggap Tumpahan Kimia',
        ]);
    }

    public function naturalDisaster(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'natural_disaster',
            'name' => 'Rencana Tanggap Bencana Alam',
        ]);
    }

    public function security(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'security',
            'name' => 'Rencana Tanggap Darurat Keamanan',
        ]);
    }
}
