<?php

namespace Database\Factories\Modules\Contractor;

use App\Models\Modules\Contractor\Contractor;
use App\Models\Modules\Contractor\ContractorEvaluation;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ContractorEvaluation>
 */
class ContractorEvaluationFactory extends Factory
{
    protected $model = ContractorEvaluation::class;

    public function definition(): array
    {
        $criteria = [
            'safety_management' => $this->faker->numberBetween(0, 20),
            'training' => $this->faker->numberBetween(0, 20),
            'incident_history' => $this->faker->numberBetween(0, 20),
            'compliance' => $this->faker->numberBetween(0, 20),
            'performance' => $this->faker->numberBetween(0, 20),
        ];

        $totalScore = (int) array_sum($criteria);

        return [
            'contractor_id' => Contractor::factory(),
            'evaluation_date' => $this->faker->dateTimeBetween('-1 year', 'now')->format('Y-m-d'),
            'evaluator_id' => User::factory(),
            'criteria' => $criteria,
            'total_score' => $totalScore,
            'result' => ContractorEvaluation::deriveResult($totalScore),
            'notes' => $this->faker->sentence(10),
            'created_by' => User::factory(),
            'updated_by' => User::factory(),
        ];
    }
}
