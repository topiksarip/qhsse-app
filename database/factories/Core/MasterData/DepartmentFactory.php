<?php

namespace Database\Factories\Core\MasterData;

use App\Models\Core\MasterData\Department;
use App\Models\Core\MasterData\Site;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Department> */
class DepartmentFactory extends Factory
{
    public function definition(): array
    {
        return [
            'site_id' => Site::factory(),
            'code' => $this->faker->unique()->bothify('DEPT-###'),
            'name' => $this->faker->randomElement(['QHSSE', 'Operations', 'Maintenance', 'Security', 'Quality']),
            'is_active' => true,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn () => ['is_active' => false]);
    }
}
