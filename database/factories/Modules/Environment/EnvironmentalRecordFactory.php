<?php

namespace Database\Factories\Modules\Environment;

use App\Models\Core\MasterData\Site;
use App\Models\Modules\Environment\EnvironmentalRecord;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<EnvironmentalRecord>
 */
class EnvironmentalRecordFactory extends Factory
{
    protected $model = EnvironmentalRecord::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $type = $this->faker->randomElement(['waste', 'spill', 'emission', 'noise', 'water_monitoring', 'other']);

        $base = [
            'record_number' => 'ENV-'.now()->year.'-'.str_pad((string) $this->faker->unique()->numberBetween(1, 9999), 4, '0', STR_PAD_LEFT),
            'type' => $type,
            'title' => $this->faker->sentence(5),
            'description' => $this->faker->paragraph(3),
            'site_id' => Site::factory(),
            'area_id' => null,
            'occurred_at' => $this->faker->dateTimeBetween('-30 days', 'now'),
            'reporter_id' => User::factory(),
            'status' => 'recorded',
            'capa_action_id' => null,
        ];

        return array_merge($base, $this->getTypeSpecificData($type));
    }

    protected function getTypeSpecificData(string $type): array
    {
        return match ($type) {
            'waste' => [
                'waste_type' => $this->faker->randomElement(['B3', 'Non-B3', 'Medis']),
                'quantity' => $this->faker->randomFloat(2, 10, 1000),
                'disposal_method' => $this->faker->randomElement(['Incinerator', 'TPA', 'Pihak Ketiga']),
            ],
            'spill' => [
                'material' => $this->faker->randomElement(['Oil', 'Chemical', 'Fuel']),
                'volume' => $this->faker->randomFloat(2, 1, 100),
                'containment' => $this->faker->randomElement(['Boom oil', 'Absorbent', 'Sand bags']),
            ],
            'emission' => [
                'parameter' => $this->faker->randomElement(['SOx', 'NOx', 'PM10', 'CO']),
                'measured_value' => $this->faker->randomFloat(2, 50, 200),
                'unit' => 'mg/m³',
                'limit_value' => 150,
            ],
            'noise' => [
                'location' => $this->faker->randomElement(['Production Area', 'Warehouse', 'Loading Dock']),
                'measured_value' => $this->faker->randomFloat(1, 70, 95),
                'unit' => 'dB',
                'limit_value' => 85,
            ],
            'water_monitoring' => [
                'parameter' => $this->faker->randomElement(['pH', 'TSS', 'BOD', 'COD']),
                'measured_value' => $this->faker->randomFloat(2, 5, 15),
                'unit' => $this->faker->randomElement(['pH', 'mg/L']),
                'limit_value' => 10,
            ],
            default => [],
        };
    }

    public function exceedance(): static
    {
        return $this->state(fn (array $attributes) => [
            'measured_value' => 200,
            'limit_value' => 150,
            'is_exceedance' => true,
        ]);
    }
}
