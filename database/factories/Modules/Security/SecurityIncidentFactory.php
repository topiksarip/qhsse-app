<?php

namespace Database\Factories\Modules\Security;

use App\Models\Core\MasterData\Severity;
use App\Models\Core\MasterData\Site;
use App\Models\Modules\Security\SecurityIncident;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SecurityIncident>
 */
class SecurityIncidentFactory extends Factory
{
    protected $model = SecurityIncident::class;

    public function definition(): array
    {
        $type = $this->faker->randomElement(['unauthorized_access', 'theft', 'vandalism', 'trespass', 'suspicious_activity', 'other']);
        $status = $this->faker->randomElement(['reported', 'under_investigation', 'closed']);

        $base = [
            'security_number' => 'SEC-'.now()->year.'-'.str_pad((string) $this->faker->unique()->numberBetween(1, 9999), 4, '0', STR_PAD_LEFT),
            'type' => $type,
            'title' => $this->faker->sentence(5),
            'description' => $this->faker->paragraph(3),
            'site_id' => Site::factory(),
            'area_id' => null,
            'occurred_at' => $this->faker->dateTimeBetween('-30 days', 'now'),
            'reported_by' => User::factory(),
            'severity_id' => Severity::factory(),
            'status' => $status,
            'resolution' => null,
            'resolved_at' => null,
        ];

        if ($status === 'closed') {
            $base['resolution'] = $this->faker->paragraph(2);
            $base['resolved_at'] = $this->faker->dateTimeBetween('-15 days', 'now');
        }

        return $base;
    }

    public function closed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'closed',
            'resolution' => $this->faker->paragraph(2),
            'resolved_at' => $this->faker->dateTimeBetween('-15 days', 'now'),
        ]);
    }

    public function underInvestigation(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'under_investigation',
        ]);
    }
}
