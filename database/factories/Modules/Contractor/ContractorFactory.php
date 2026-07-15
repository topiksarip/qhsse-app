<?php

namespace Database\Factories\Modules\Contractor;

use App\Models\Modules\Contractor\Contractor;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Contractor>
 */
class ContractorFactory extends Factory
{
    protected $model = Contractor::class;

    public function definition(): array
    {
        return [
            'contractor_number' => 'CTR-' . now()->year . '-' . str_pad((string) $this->faker->unique()->numberBetween(1, 9999), 4, '0', STR_PAD_LEFT),
            'company_name' => $this->faker->company(),
            'business_registration_number' => $this->faker->numerify('BRN-#######'),
            'tax_id' => $this->faker->numerify('TAX-#######'),
            'contact_person' => $this->faker->name(),
            'contact_phone' => $this->faker->phoneNumber(),
            'contact_email' => $this->faker->safeEmail(),
            'address' => $this->faker->address(),
            'business_type' => $this->faker->randomElement(['construction', 'maintenance', 'cleaning', 'security', 'transportation', 'consulting', 'technical', 'catering', 'other']),
            'scope_of_work' => $this->faker->sentence(8),
            'specialization' => $this->faker->word(),
            'contract_start_date' => $this->faker->dateTimeBetween('-1 year', 'now')->format('Y-m-d'),
            'contract_end_date' => $this->faker->dateTimeBetween('now', '+1 year')->format('Y-m-d'),
            'contract_status' => $this->faker->randomElement(['pending', 'active', 'suspended', 'expired', 'terminated']),
            'contract_terms' => $this->faker->paragraph(2),
            'safety_induction_required' => true,
            'safety_induction_date' => $this->faker->dateTimeBetween('-6 months', 'now')->format('Y-m-d'),
            'safety_induction_expiry' => $this->faker->dateTimeBetween('now', '+6 months')->format('Y-m-d'),
            'insurance_required' => true,
            'insurance_policy_number' => $this->faker->numerify('INS-#######'),
            'insurance_expiry' => $this->faker->dateTimeBetween('now', '+6 months')->format('Y-m-d'),
            'performance_rating' => $this->faker->randomFloat(2, 1, 5),
            'incident_count' => $this->faker->numberBetween(0, 10),
            'violation_count' => $this->faker->numberBetween(0, 5),
            'performance_notes' => $this->faker->sentence(10),
            'authorized_sites' => null,
            'authorized_areas' => null,
            'document_files' => null,
            'approval_status' => $this->faker->randomElement(['draft', 'submitted', 'approved', 'rejected']),
            'approved_by' => null,
            'approved_at' => null,
            'approval_notes' => null,
            'is_prequalified' => false,
            'prequalified_until' => null,
            'safety_rating' => null,
            'created_by' => User::factory(),
            'updated_by' => User::factory(),
        ];
    }
}
