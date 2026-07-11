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
            'plan_number' => 'EMG-' . date('Y') . '-' . str_pad((string) fake()->unique()->numberBetween(1, 9999), 4, '0', STR_PAD_LEFT),
            'name' => fake()->sentence(4),
            'type' => fake()->randomElement($types),
            'site_id' => Site::factory(),
            'description' => fake()->paragraph(3),
            'response_procedure' => fake()->paragraphs(5, true),
            'escalation_procedure' => fake()->paragraphs(3, true),
            'contact_person_id' => User::factory(),
            'emergency_contacts' => fake()->optional(0.6)->passthrough([
                [
                    'name' => fake()->name(),
                    'role' => fake()->jobTitle(),
                    'phone' => fake()->phoneNumber(),
                ],
                [
                    'name' => fake()->name(),
                    'role' => fake()->jobTitle(),
                    'phone' => fake()->phoneNumber(),
                ],
            ]),
            'equipment_needed' => fake()->optional(0.7)->paragraph(2),
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
