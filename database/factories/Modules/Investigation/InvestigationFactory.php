<?php

namespace Database\Factories\Modules\Investigation;

use App\Models\Modules\Incident\IncidentReport;
use App\Models\Modules\Investigation\Investigation;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Investigation> */
class InvestigationFactory extends Factory
{
    public function definition(): array
    {
        return [
            'investigation_number' => 'INV-' . now()->year . '-' . str_pad((string) fake()->numberBetween(1, 9999), 4, '0', STR_PAD_LEFT),
            'incident_id' => IncidentReport::factory(),
            'title' => fake()->sentence(6),
            'status' => 'draft',
            'root_cause' => null,
            'five_whys' => null,
            'fishbone' => null,
            'contributing_factors' => null,
            'timeline_events' => null,
            'recommendations' => null,
            'investigator_id' => User::factory(),
            'started_at' => null,
            'completed_at' => null,
        ];
    }
}
