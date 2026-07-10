<?php

namespace Database\Factories\Modules\Incident;

use App\Models\Core\MasterData\Priority;
use App\Models\Core\MasterData\Severity;
use App\Models\Core\MasterData\Site;
use App\Models\Modules\Incident\IncidentReport;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<IncidentReport> */
class IncidentReportFactory extends Factory
{
    public function definition(): array
    {
        return [
            'incident_number' => 'INC-' . now()->year . '-' . str_pad((string) fake()->numberBetween(1, 9999), 4, '0', STR_PAD_LEFT),
            'title' => fake()->sentence(6),
            'category' => fake()->randomElement([
                'accident', 'incident', 'near_miss', 'unsafe_act',
                'unsafe_condition', 'environmental_spill', 'security_breach',
            ]),
            'occurred_at' => fake()->dateTimeBetween('-1 month', 'now'),
            'site_id' => Site::factory(),
            'area_id' => null,
            'department_id' => null,
            'reporter_id' => User::factory(),
            'severity_id' => Severity::factory(),
            'priority_id' => Priority::factory(),
            'description' => fake()->paragraph(3),
            'immediate_action' => fake()->optional(0.7)->paragraph(2),
            'status' => 'draft',
        ];
    }
}
