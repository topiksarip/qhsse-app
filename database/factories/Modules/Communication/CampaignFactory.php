<?php

namespace Database\Factories\Modules\Communication;

use App\Models\Modules\Communication\Campaign;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Campaign>
 */
class CampaignFactory extends Factory
{
    protected $model = Campaign::class;

    public function definition(): array
    {
        return [
            'campaign_number' => 'CMP-'.now()->year.'-'.str_pad((string) $this->faker->unique()->numberBetween(1, 9999), 4, '0', STR_PAD_LEFT),
            'title' => $this->faker->sentence(4),
            'type' => $this->faker->randomElement(['safety_alert', 'lesson_learned', 'campaign', 'announcement', 'newsletter']),
            'content' => $this->faker->paragraph(),
            'target_audience' => 'all',
            'site_id' => null,
            'department_id' => null,
            'target_role' => null,
            'status' => 'draft',
            'author_id' => User::factory(),
            'created_by' => User::factory(),
            'updated_by' => User::factory(),
        ];
    }

    public function published(): static
    {
        return $this->state(fn () => [
            'status' => 'published',
            'published_at' => now(),
        ]);
    }
}
