<?php

namespace Database\Factories\Modules\Reporting;

use App\Models\Modules\Reporting\ReportTemplate;
use App\Models\Modules\Reporting\SavedReport;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SavedReport>
 */
class SavedReportFactory extends Factory
{
    protected $model = SavedReport::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->words(3, true),
            'template_id' => ReportTemplate::factory(),
            'status' => 'completed',
            'parameters' => [],
            'format' => 'csv',
            'file_path' => null,
            'file_size' => null,
            'generated_by' => User::factory(),
            'generated_at' => now(),
            'completed_at' => now(),
            'failed_at' => null,
            'error_message' => null,
            'created_by' => User::factory(),
            'updated_by' => User::factory(),
        ];
    }

    public function pending(): static
    {
        return $this->state(fn () => [
            'status' => 'pending',
            'completed_at' => null,
            'generated_at' => now(),
        ]);
    }

    public function processing(): static
    {
        return $this->state(fn () => [
            'status' => 'processing',
            'completed_at' => null,
        ]);
    }

    public function failed(): static
    {
        return $this->state(fn () => [
            'status' => 'failed',
            'failed_at' => now(),
            'error_message' => 'Generation failed',
        ]);
    }
}
