<?php

namespace Database\Factories\Core\MasterData;

use App\Models\Core\MasterData\Department;
use App\Models\Core\MasterData\Position;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Position> */
class PositionFactory extends Factory
{
    public function definition(): array
    {
        return [
            'department_id' => Department::factory(),
            'code' => fake()->unique()->bothify('POS-###'),
            'name' => fake()->jobTitle(),
            'is_active' => true,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn () => ['is_active' => false]);
    }
}
