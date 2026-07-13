<?php

namespace Database\Factories\Modules\Quality;

use App\Models\Core\MasterData\Severity;
use App\Models\Core\MasterData\Site;
use App\Models\Modules\Quality\Ncr;
use Illuminate\Database\Eloquent\Factories\Factory;

class NcrFactory extends Factory
{
    protected $model = Ncr::class;

    public function definition(): array
    {
        $source = $this->faker->randomElement(['internal', 'external', 'customer_complaint', 'audit', 'supplier']);
        $status = $this->faker->randomElement(['open', 'under_review', 'in_progress', 'closed']);

        return [
            'ncr_number' => 'NCR-'.now()->year.'-'.str_pad((string) $this->faker->unique()->numberBetween(1, 9999), 4, '0', STR_PAD_LEFT),
            'title' => $this->faker->sentence(5), 'source' => $source, 'description' => $this->faker->paragraph(3),
            'site_id' => Site::factory(), 'department_id' => null, 'product_service' => $this->faker->optional()->words(3, true),
            'batch_lot' => $this->faker->optional()->numerify('LOT-####'), 'customer_name' => $source === 'customer_complaint' ? $this->faker->company() : null,
            'severity_id' => Severity::factory(), 'status' => $status,
            'root_cause' => $status === 'closed' ? $this->faker->paragraph(2) : null,
            'corrective_action' => $status === 'closed' ? $this->faker->paragraph(2) : null,
            'preventive_action' => $status === 'closed' ? $this->faker->paragraph(1) : null,
            'capa_action_id' => null, 'closed_at' => $status === 'closed' ? $this->faker->dateTimeBetween('-15 days', 'now') : null,
        ];
    }
}
