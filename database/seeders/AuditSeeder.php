<?php

namespace Database\Seeders;

use App\Models\Core\MasterData\Department;
use App\Models\Modules\Audit\Audit;
use App\Models\Modules\Audit\AuditFinding;
use App\Models\User;
use Illuminate\Database\Seeder;

class AuditSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('Seeding Audit Management demo data...');

        $departments = Department::query()->where('is_active', true)->get();
        $users = User::query()->where('is_active', true)->get();

        if ($departments->isEmpty() || $users->isEmpty()) {
            $this->command->warn('Skipping Audit seeder: no active departments or users found.');

            return;
        }

        // Planned audit
        $plannedAudit = Audit::factory()
            ->planned()
            ->create([
                'department_id' => $departments->random()->id,
                'lead_auditor_id' => $users->random()->id,
                'created_by' => $users->random()->id,
            ]);

        // In progress audit with findings
        $inProgressAudit = Audit::factory()
            ->inProgress()
            ->create([
                'department_id' => $departments->random()->id,
                'lead_auditor_id' => $users->random()->id,
                'created_by' => $users->random()->id,
            ]);

        AuditFinding::factory()
            ->count(3)
            ->create([
                'audit_id' => $inProgressAudit->id,
                'finding_number' => fn ($attributes, $index) => "{$inProgressAudit->audit_number}-F".str_pad((string) ($index + 1), 2, '0', STR_PAD_LEFT),
            ]);

        // Report ready audit with findings
        $reportReadyAudit = Audit::factory()
            ->reportReady()
            ->create([
                'department_id' => $departments->random()->id,
                'lead_auditor_id' => $users->random()->id,
                'created_by' => $users->random()->id,
            ]);

        AuditFinding::factory()
            ->count(2)
            ->major()
            ->create([
                'audit_id' => $reportReadyAudit->id,
                'finding_number' => fn ($attributes, $index) => "{$reportReadyAudit->audit_number}-F".str_pad((string) ($index + 1), 2, '0', STR_PAD_LEFT),
            ]);

        AuditFinding::factory()
            ->minor()
            ->closed()
            ->create([
                'audit_id' => $reportReadyAudit->id,
                'finding_number' => "{$reportReadyAudit->audit_number}-F03",
                'closed_by' => $users->random()->id,
            ]);

        // Closed audit with all findings closed
        $closedAudit = Audit::factory()
            ->closed()
            ->create([
                'department_id' => $departments->random()->id,
                'lead_auditor_id' => $users->random()->id,
                'created_by' => $users->random()->id,
            ]);

        AuditFinding::factory()
            ->count(2)
            ->closed()
            ->create([
                'audit_id' => $closedAudit->id,
                'finding_number' => fn ($attributes, $index) => "{$closedAudit->audit_number}-F".str_pad((string) ($index + 1), 2, '0', STR_PAD_LEFT),
                'closed_by' => $users->random()->id,
            ]);

        $this->command->info('Audit Management demo data seeded successfully.');
    }
}
