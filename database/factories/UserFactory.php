<?php

namespace Database\Factories;

use App\Models\Core\MasterData\Company;
use App\Models\Core\Users\Employee;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'company_id' => null,
            'employee_id' => null,
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'is_active' => true,
            'password' => static::$password ??= Hash::make('password'),
            'remember_token' => Str::random(10),
        ];
    }

    /**
     * Indicate that the model's email address should be unverified.
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }

    /**
     * Indicate that the user account is inactive and cannot sign in.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    public function linkedToEmployee(?Employee $employee = null): static
    {
        return $this->state(function (array $attributes) use ($employee) {
            $employee ??= Employee::factory()->create();

            return [
                'company_id' => $employee->company_id,
                'employee_id' => $employee->id,
                'name' => $employee->name,
                'email' => $employee->email ?? fake()->unique()->safeEmail(),
            ];
        });
    }

    public function linkedToCompany(?Company $company = null): static
    {
        return $this->state(fn (array $attributes) => [
            'company_id' => ($company ?? Company::factory()->create())->id,
        ]);
    }
}
