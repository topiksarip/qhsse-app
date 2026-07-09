<?php

namespace Database\Factories\Core\Users;

use App\Models\Core\MasterData\Company;
use App\Models\Core\MasterData\Department;
use App\Models\Core\MasterData\Position;
use App\Models\Core\MasterData\Site;
use App\Models\Core\Users\Employee;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Employee>
 */
class EmployeeFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $department = Department::factory()->create();
        $position = Position::factory()->for($department)->create();

        return [
            'company_id' => Company::factory(),
            'site_id' => $department->site_id,
            'department_id' => $department->id,
            'position_id' => $position->id,
            'employee_no' => fake()->unique()->bothify('EMP-#####'),
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'phone' => fake()->phoneNumber(),
            'department' => fake()->randomElement(['QHSSE', 'Operations', 'Maintenance', 'Security']),
            'position' => fake()->jobTitle(),
            'is_active' => true,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    public function forSite(Site $site): static
    {
        return $this->state(fn () => [
            'site_id' => $site->id,
        ]);
    }
}
