<?php

namespace Database\Factories\Modules\Permit;

use App\Models\Modules\Permit\Permit;
use App\Models\Modules\Permit\PermitChecklist;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PermitChecklist>
 */
class PermitChecklistFactory extends Factory
{
    protected $model = PermitChecklist::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'permit_id' => Permit::factory(),
            'item_text' => $this->faker->sentence(8),
            'is_checked' => false,
            'checked_by' => null,
            'checked_at' => null,
        ];
    }

    public function checked(): static
    {
        return $this->state(function (array $attributes) {
            $checker = User::factory()->create();

            return [
                'is_checked' => true,
                'checked_by' => $checker->id,
                'checked_at' => now()->subMinutes($this->faker->numberBetween(5, 120)),
            ];
        });
    }
}
