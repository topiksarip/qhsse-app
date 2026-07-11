<?php

namespace Database\Seeders;

use App\Models\Core\Users\Employee;
use App\Models\Modules\Training\TrainingProgram;
use App\Models\Modules\Training\TrainingRecord;
use Illuminate\Database\Seeder;

class TrainingSeeder extends Seeder
{
    /**
     * Run the database seeder.
     */
    public function run(): void
    {
        // Check if we have employees
        if (Employee::count() === 0) {
            $this->command->warn('No employees found. Skipping TrainingSeeder.');
            return;
        }

        // Create sample training programs
        $programs = [
            [
                'code' => 'HSE-IND',
                'name' => 'HSE Induction',
                'description' => 'Pelatihan induksi HSE untuk karyawan baru',
                'category' => 'safety',
                'duration_hours' => 8,
                'is_certification' => true,
                'validity_months' => 12,
                'is_active' => true,
            ],
            [
                'code' => 'FIRE-01',
                'name' => 'Fire Safety Training',
                'description' => 'Pelatihan pencegahan dan pemadaman kebakaran',
                'category' => 'safety',
                'duration_hours' => 4,
                'is_certification' => true,
                'validity_months' => 24,
                'is_active' => true,
            ],
            [
                'code' => 'FORK-01',
                'name' => 'Forklift Operation',
                'description' => 'Pelatihan operasi forklift yang aman',
                'category' => 'technical',
                'duration_hours' => 16,
                'is_certification' => true,
                'validity_months' => 36,
                'is_active' => true,
            ],
            [
                'code' => 'P3K-01',
                'name' => 'First Aid & CPR',
                'description' => 'Pelatihan pertolongan pertama dan CPR',
                'category' => 'first_aid',
                'duration_hours' => 8,
                'is_certification' => true,
                'validity_months' => 24,
                'is_active' => true,
            ],
            [
                'code' => 'ENV-01',
                'name' => 'Environmental Awareness',
                'description' => 'Pelatihan kesadaran lingkungan',
                'category' => 'environment',
                'duration_hours' => 4,
                'is_certification' => false,
                'validity_months' => null,
                'is_active' => true,
            ],
        ];

        foreach ($programs as $programData) {
            TrainingProgram::firstOrCreate(
                ['code' => $programData['code']],
                $programData
            );
        }

        $this->command->info('Training programs seeded successfully.');

        // Create sample training records
        $employees = Employee::where('is_active', true)->limit(5)->get();
        $createdPrograms = TrainingProgram::all();

        if ($employees->isEmpty() || $createdPrograms->isEmpty()) {
            $this->command->warn('No active employees or programs found. Skipping training records.');
            return;
        }

        foreach ($employees as $employee) {
            // Each employee gets 2-3 random training records
            $randomPrograms = $createdPrograms->random(min(3, $createdPrograms->count()));

            foreach ($randomPrograms as $program) {
                TrainingRecord::factory()
                    ->for($employee)
                    ->for($program)
                    ->create();
            }
        }

        $this->command->info('Training records seeded successfully.');
    }
}
