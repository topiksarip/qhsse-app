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
            'code' => $this->faker->unique()->bothify('TPL-###'),
            'name' => $this->faker->sentence(3).' Checklist',
            'description' => $this->faker->optional(0.7)->paragraph(),
            'category' => $this->faker->randomElement(['safety', 'environment', 'equipment', 'fire', 'housekeeping', 'security', 'quality', 'compliance']),
            'is_active' => true,
        ];
    }
}
