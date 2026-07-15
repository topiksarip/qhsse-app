<?php

namespace Database\Factories\Modules\Reporting;

use App\Models\Modules\Reporting\ReportTemplate;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ReportTemplate>
 */
class ReportTemplateFactory extends Factory
{
    protected $model = ReportTemplate::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->words(3, true),
            'type' => 'custom',
            'description' => $this->faker->sentence(),
            'config' => ['sections' => []],
            'is_active' => true,
            'is_predefined' => false,
            'created_by' => User::factory(),
            'updated_by' => User::factory(),
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn () => ['is_active' => false]);
    }

    public function predefined(): static
    {
        return $this->state(fn () => ['is_predefined' => true]);
    }
}
