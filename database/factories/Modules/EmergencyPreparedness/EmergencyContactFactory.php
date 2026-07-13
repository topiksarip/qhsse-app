<?php

declare(strict_types=1);

namespace Database\Factories\Modules\EmergencyPreparedness;

use App\Models\Core\MasterData\Site;
use App\Models\Modules\EmergencyPreparedness\EmergencyContact;
use Illuminate\Database\Eloquent\Factories\Factory;

class EmergencyContactFactory extends Factory
{
    protected $model = EmergencyContact::class;

    public function definition(): array
    {
        $roles = [
            'Fire Warden',
            'First Aider',
            'Site Security',
            'ERT Leader',
            'Emergency Coordinator',
            'Safety Officer',
            'Site Manager',
            'HSE Coordinator',
        ];

        return [
            'name' => $this->faker->name(),
            'role' => $this->faker->randomElement($roles),
            'phone' => $this->faker->phoneNumber(),
            'email' => $this->faker->optional(0.7)->safeEmail(),
            'site_id' => Site::factory(),
            'is_active' => true,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    public function fireWarden(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => 'Fire Warden',
        ]);
    }

    public function firstAider(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => 'First Aider',
        ]);
    }

    public function ertLeader(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => 'ERT Leader',
        ]);
    }
}
