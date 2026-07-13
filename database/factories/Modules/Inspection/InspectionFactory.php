<?php

namespace Database\Factories\Modules\Inspection;

use App\Models\Core\MasterData\Site;
use App\Models\Modules\Inspection\Inspection;
use App\Models\Modules\Inspection\InspectionTemplate;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Inspection> */
class InspectionFactory extends Factory
{
    public function definition(): array
    {
        return [
            'inspection_number' => 'INS-'.now()->year.'-'.str_pad((string) $this->faker->numberBetween(1, 9999), 4, '0', STR_PAD_LEFT),
            'inspection_template_id' => InspectionTemplate::factory(),
            'site_id' => Site::factory(),
            'area_id' => null,
            'inspector_id' => User::factory(),
            'scheduled_at' => $this->faker->dateTimeBetween('now', '+1 month')->format('Y-m-d'),
            'executed_at' => null,
            'status' => 'pending',
            'overall_result' => 'pending',
            'notes' => null,
        ];
    }
}
