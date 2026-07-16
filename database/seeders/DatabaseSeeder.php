<?php

namespace Database\Seeders;

use App\Models\User;
use Database\Seeders\AuditSeeder;
use Database\Seeders\Modules\Reporting\ReportTemplateSeeder;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            RolesAndPermissionsSeeder::class,
            QhsseMasterDataSeeder::class,
            NumberingFormatSeeder::class,
            WorkflowSeeder::class,
            NotificationTemplateSeeder::class,
        ]);

        $user = User::query()->firstOrCreate(
            ['email' => 'test@example.com'],
            [
                'name' => 'Test User',
                'email_verified_at' => now(),
                'is_active' => true,
                'password' => Hash::make('password'),
            ],
        );

        $user->assignRole('Super Admin');

        $this->call([
            IncidentReportingSeeder::class,
            InvestigationSeeder::class,
            CapaSeeder::class,
            InspectionSeeder::class,
            DocumentControlSeeder::class,
            AuditSeeder::class,
            CampaignSeeder::class,
            ReportTemplateSeeder::class,
            ApdSeeder::class,
        ]);
    }
}
