<?php

namespace Database\Factories\Modules\Asset;

use App\Models\Modules\Asset\Asset;
use App\Models\Core\MasterData\Site;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Asset> */
class AssetFactory extends Factory
{
    protected $model = Asset::class;

    public function definition(): array
    {
        return [
            'asset_number' => 'AST-' . fake()->unique()->numerify('######'),
            'name' => fake()->words(2, true),
            'category' => 'equipment',
            'serial_number' => fake()->bothify('SN-####??'),
            'model' => fake()->word(),
            'manufacturer' => fake()->company(),
            'site_id' => Site::factory(),
            'status' => 'active',
            'created_by' => User::factory(),
            'updated_by' => User::factory(),
        ];
    }
}
