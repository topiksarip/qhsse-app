<?php
namespace Database\Factories\Modules\Quality;
use App\Models\Core\MasterData\{Severity, Site};
use App\Models\Modules\Quality\Ncr;
use Illuminate\Database\Eloquent\Factories\Factory;
class NcrFactory extends Factory {
    protected $model = Ncr::class;
    public function definition(): array {
        $source = fake()->randomElement(['internal','external','customer_complaint','audit','supplier']);
        $status = fake()->randomElement(['open','under_review','in_progress','closed']);
        return [
            'ncr_number' => 'NCR-'.now()->year.'-'.str_pad((string)fake()->unique()->numberBetween(1,9999),4,'0',STR_PAD_LEFT),
            'title' => fake()->sentence(5), 'source' => $source, 'description' => fake()->paragraph(3),
            'site_id' => Site::factory(), 'department_id' => null, 'product_service' => fake()->optional()->words(3,true),
            'batch_lot' => fake()->optional()->numerify('LOT-####'), 'customer_name' => $source==='customer_complaint'?fake()->company():null,
            'severity_id' => Severity::factory(), 'status' => $status,
            'root_cause' => $status==='closed'?fake()->paragraph(2):null,
            'corrective_action' => $status==='closed'?fake()->paragraph(2):null,
            'preventive_action' => $status==='closed'?fake()->paragraph(1):null,
            'capa_action_id' => null, 'closed_at' => $status==='closed'?fake()->dateTimeBetween('-15 days','now'):null,
        ];
    }
}
