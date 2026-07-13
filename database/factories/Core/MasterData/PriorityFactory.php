<?php

namespace Database\Factories\Core\MasterData;

use App\Models\Core\MasterData\Priority;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Priority> */ class PriorityFactory extends Factory
{
    public function definition(): array
    {
        return ['code' => $this->faker->unique()->bothify('PRI-##'), 'name' => $this->faker->word(), 'sla_days' => $this->faker->numberBetween(1, 30), 'color' => 'gray', 'is_active' => true];
    }

    public function inactive(): static
    {
        return $this->state(fn () => ['is_active' => false]);
    }
}
