<?php

declare(strict_types=1);

namespace Database\Factories\Modules\RiskManagement;

use App\Models\Core\MasterData\RiskMatrixLevel;
use App\Models\Core\MasterData\Severity;
use App\Models\Core\MasterData\Site;
use App\Models\Modules\RiskManagement\RiskRegister;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<RiskRegister>
 */
class RiskRegisterFactory extends Factory
{
    protected $model = RiskRegister::class;

    public function definition(): array
    {
        return [
            'register_number' => 'RSK-'.now()->year.'-'.str_pad((string) $this->faker->numberBetween(1, 9999), 4, '0', STR_PAD_LEFT),
            'title' => $this->faker->sentence(6),
            'type' => $this->faker->randomElement([
                'hazard_identification',
                'jsa',
                'hiradc',
                'risk_assessment',
            ]),
            'site_id' => Site::factory(),
            'area_id' => null,
            'department_id' => null,
            'activity' => $this->faker->sentence(8),
            'hazard' => $this->faker->paragraph(2),
            'existing_controls' => $this->faker->optional(0.7)->paragraph(2),
            'severity_id' => null,
            'probability_id' => null,
            'risk_level_id' => null,
            'additional_controls' => null,
            'residual_severity_id' => null,
            'residual_probability_id' => null,
            'residual_risk_level_id' => null,
            'owner_id' => User::factory(),
            'status' => 'identified',
            'review_date' => $this->faker->optional()->dateTimeBetween('+1 month', '+6 months'),
        ];
    }

    public function withRiskAssessment(): static
    {
        return $this->state(function (array $attributes) {
            $severity = Severity::inRandomOrder()->first() ?? Severity::factory()->create();
            $probabilityLevel = $this->faker->numberBetween(1, 5);
            $riskLevel = RiskMatrixLevel::where('severity_level', $severity->level)
                ->where('probability_level', $probabilityLevel)
                ->where('is_active', true)
                ->first();

            return [
                'severity_id' => $severity->id,
                'probability_id' => $probabilityLevel,
                'risk_level_id' => $riskLevel?->id,
                'status' => 'assessed',
            ];
        });
    }

    public function withResidualRisk(): static
    {
        return $this->withRiskAssessment()->state(function (array $attributes) {
            $residualSeverity = Severity::inRandomOrder()->first() ?? Severity::factory()->create();
            $residualProbabilityLevel = $this->faker->numberBetween(1, 5);
            $residualRiskLevel = RiskMatrixLevel::where('severity_level', $residualSeverity->level)
                ->where('probability_level', $residualProbabilityLevel)
                ->where('is_active', true)
                ->first();

            return [
                'additional_controls' => $this->faker->paragraph(3),
                'residual_severity_id' => $residualSeverity->id,
                'residual_probability_id' => $residualProbabilityLevel,
                'residual_risk_level_id' => $residualRiskLevel?->id,
                'status' => 'controls_in_place',
            ];
        });
    }

    public function obsolete(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'obsolete',
        ]);
    }

    public function monitored(): static
    {
        return $this->withResidualRisk()->state(fn (array $attributes) => [
            'status' => 'monitored',
        ]);
    }
}
