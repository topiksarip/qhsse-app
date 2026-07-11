<?php

namespace Database\Factories\Modules\Inspection;

use App\Models\Modules\Inspection\InspectionTemplate;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<InspectionTemplate> */
class InspectionTemplateFactory extends Factory
{
    public function definition(): array
    {
        return [
            'code' => fake()->unique()->bothify('TPL-###'),
            'name' => fake()->sentence(3) . ' Checklist',
            'description' => fake()->optional(0.7)->paragraph(),
            'category' => fake()->randomElement(['safety', 'environment', 'equipment', 'fire', 'housekeeping', 'security', 'quality', 'compliance']),
            'is_active' => true,
        ];
    }
}
