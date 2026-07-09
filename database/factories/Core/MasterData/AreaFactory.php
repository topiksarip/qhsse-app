<?php

namespace Database\Factories\Core\MasterData;

use App\Models\Core\MasterData\Area;
use App\Models\Core\MasterData\Site;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Area> */
class AreaFactory extends Factory
{
    public function definition(): array
    {
        return [
            'site_id' => Site::factory(),
            'code' => fake()->unique()->bothify('AREA-###'),
            'name' => fake()->streetName(),
            'type' => fake()->randomElement(['office', 'workshop', 'warehouse', 'field']),
            'is_active' => true,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn () => ['is_active' => false]);
    }
}
