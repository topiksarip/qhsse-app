<?php

namespace Database\Factories\Modules\DocumentControl;

use App\Models\Core\MasterData\Department;
use App\Models\Modules\DocumentControl\ControlledDocument;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<ControlledDocument> */
class ControlledDocumentFactory extends Factory
{
    public function definition(): array
    {
        $types = ['sop', 'wi', 'jsa', 'hiradc', 'msds', 'policy', 'form', 'manual', 'other'];
        $statuses = ['draft', 'review', 'approved', 'effective', 'obsolete', 'rejected'];

        return [
            'document_number' => 'DOC-'.date('Y').'-'.str_pad((string) $this->faker->numberBetween(1, 9999), 4, '0', STR_PAD_LEFT),
            'title' => $this->faker->sentence(4),
            'type' => $this->faker->randomElement($types),
            'version' => $this->faker->randomElement(['1.0', '1.1', '2.0', '3.0']),
            'revision_notes' => $this->faker->optional()->paragraph(),
            'effective_date' => $this->faker->optional()->date(),
            'review_date' => $this->faker->optional()->dateTimeBetween('+1 month', '+6 months')?->format('Y-m-d'),
            'expiry_date' => $this->faker->optional()->dateTimeBetween('+6 months', '+2 years')?->format('Y-m-d'),
            'department_id' => Department::inRandomOrder()->first()?->id,
            'owner_id' => User::inRandomOrder()->first()?->id ?? User::factory(),
            'approver_id' => null,
            'status' => $this->faker->randomElement($statuses),
            'is_confidential' => $this->faker->boolean(15),
        ];
    }
}
