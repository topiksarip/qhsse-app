<?php

namespace Database\Factories\Core\Numbering;

use App\Models\Core\Numbering\NumberingFormat;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<NumberingFormat> */
class NumberingFormatFactory extends Factory
{
    protected $model = NumberingFormat::class;

    public function definition(): array
    {
        $prefix = strtoupper($this->faker->lexify('???'));

        return [
            'module_name' => 'core.'.$this->faker->unique()->slug(2),
            'prefix' => $prefix,
            'padding' => 4,
            'separator' => '-',
            'reset_frequency' => 'yearly',
            'include_year' => true,
            'include_site_code' => false,
            'sample' => $prefix.'-'.now()->year.'-0001',
            'is_active' => true,
        ];
    }
}
