<?php

namespace Database\Factories\Core\MasterData;

use App\Models\Core\MasterData\RiskMatrixLevel;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<RiskMatrixLevel> */ class RiskMatrixLevelFactory extends Factory
{
    public function definition(): array
    {
        return ['likelihood' => $this->faker->numberBetween(1, 5), 'consequence' => $this->faker->numberBetween(1, 5), 'score' => $this->faker->numberBetween(1, 25), 'level' => $this->faker->randomElement(['Low', 'Medium', 'High', 'Extreme']), 'color' => 'gray', 'description' => $this->faker->sentence(), 'is_active' => true];
    }

    public function inactive(): static
    {
        return $this->state(fn () => ['is_active' => false]);
    }
}
