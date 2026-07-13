<?php

namespace Database\Factories\Modules\Audit;

use App\Models\Modules\Audit\Audit;
use App\Models\Modules\Audit\AuditFinding;
use App\Models\Modules\Capa\CapaAction;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AuditFinding>
 */
class AuditFindingFactory extends Factory
{
    protected $model = AuditFinding::class;

    public function definition(): array
    {
        return [
            'audit_id' => Audit::factory(),
            'finding_number' => 'AUD-'.now()->year.'-0001-F01',
            'classification' => $this->faker->randomElement(['major', 'minor', 'observation']),
            'description' => $this->faker->paragraph(3),
            'recommendation' => $this->faker->optional(0.7)->paragraph(2),
            'capa_action_id' => null,
            'status' => 'open',
            'due_date' => null,
            'closed_date' => null,
            'closed_by' => null,
        ];
    }

    public function major(): static
    {
        return $this->state(fn (array $attributes) => ['classification' => 'major']);
    }

    public function minor(): static
    {
        return $this->state(fn (array $attributes) => ['classification' => 'minor']);
    }

    public function observation(): static
    {
        return $this->state(fn (array $attributes) => ['classification' => 'observation']);
    }

    public function withCapa(): static
    {
        return $this->state(fn (array $attributes) => [
            'capa_action_id' => CapaAction::factory(),
        ]);
    }

    public function closed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'closed',
            'closed_date' => now(),
            'closed_by' => User::factory(),
        ]);
    }
}
