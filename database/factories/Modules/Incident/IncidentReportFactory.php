<?php

namespace Database\Factories\Modules\Incident;

use App\Models\Modules\Incident\IncidentReport;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class IncidentReportFactory extends Factory
{
    protected $model = IncidentReport::class;

    public function definition(): array
    {
        return [
            'number' => 'IR-' . $this->faker->unique()->numerify('#######'),
            'title' => $this->faker->sentence(4),
            'description' => $this->faker->paragraph(),
            'status' => $this->faker->randomElement(IncidentReport::STATUSES),
            'event_date' => $this->faker->dateTimeBetween('-1 month', 'now'),
            'reporter_id' => User::factory(),
            'created_by' => fn (array $attrs) => $attrs['reporter_id'],
        ];
    }

    public function draft(): static
    {
        return $this->state(fn () => ['status' => 'draft']);
    }
}
