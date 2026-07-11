<?php

declare(strict_types=1);

namespace Database\Factories\Modules\LegalCompliance;

use App\Models\Core\MasterData\Department;
use App\Models\Core\MasterData\Site;
use App\Models\Modules\DocumentControl\Document;
use App\Models\Modules\LegalCompliance\LegalRegister;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class LegalRegisterFactory extends Factory
{
    protected $model = LegalRegister::class;

    public function definition(): array
    {
        $categories = ['national', 'regional', 'industry', 'internal'];
        $complianceStatuses = ['compliant', 'non_compliant', 'in_progress', 'not_applicable'];
        
        return [
            'register_number' => 'LEG-' . date('Y') . '-' . str_pad((string) fake()->unique()->numberBetween(1, 9999), 4, '0', STR_PAD_LEFT),
            'title' => fake()->sentence(6),
            'regulation_name' => fake()->sentence(8),
            'regulation_number' => 'UU No. ' . fake()->numberBetween(1, 50) . ' Tahun ' . fake()->year(),
            'issuing_body' => fake()->randomElement(['Pemerintah RI', 'Kemenaker', 'Kementerian ESDM', 'Kementerian LHK', 'Pemerintah Daerah']),
            'category' => fake()->randomElement($categories),
            'compliance_status' => fake()->randomElement($complianceStatuses),
            'site_id' => Site::factory(),
            'department_id' => Department::factory(),
            'owner_id' => User::factory(),
            'next_review_date' => fake()->optional(0.7)->dateTimeBetween('now', '+1 year'),
            'document_id' => fake()->optional(0.5)->randomElement([null, Document::factory()]),
            'notes' => fake()->optional(0.6)->paragraph(),
            'status' => 'active',
        ];
    }

    public function compliant(): static
    {
        return $this->state(fn (array $attributes) => [
            'compliance_status' => 'compliant',
        ]);
    }

    public function nonCompliant(): static
    {
        return $this->state(fn (array $attributes) => [
            'compliance_status' => 'non_compliant',
        ]);
    }

    public function inProgress(): static
    {
        return $this->state(fn (array $attributes) => [
            'compliance_status' => 'in_progress',
        ]);
    }

    public function notApplicable(): static
    {
        return $this->state(fn (array $attributes) => [
            'compliance_status' => 'not_applicable',
        ]);
    }

    public function national(): static
    {
        return $this->state(fn (array $attributes) => [
            'category' => 'national',
            'issuing_body' => 'Pemerintah RI',
        ]);
    }

    public function regional(): static
    {
        return $this->state(fn (array $attributes) => [
            'category' => 'regional',
            'issuing_body' => 'Pemerintah Daerah',
        ]);
    }

    public function industry(): static
    {
        return $this->state(fn (array $attributes) => [
            'category' => 'industry',
            'issuing_body' => 'Badan Standardisasi Nasional',
        ]);
    }

    public function internal(): static
    {
        return $this->state(fn (array $attributes) => [
            'category' => 'internal',
            'issuing_body' => 'Manajemen Perusahaan',
        ]);
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'inactive',
        ]);
    }

    public function withReviewDue(int $days = 30): static
    {
        return $this->state(fn (array $attributes) => [
            'next_review_date' => now()->addDays($days)->toDateString(),
        ]);
    }

    public function reviewOverdue(): static
    {
        return $this->state(fn (array $attributes) => [
            'next_review_date' => now()->subDays(10)->toDateString(),
        ]);
    }
}
