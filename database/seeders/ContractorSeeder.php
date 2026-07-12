<?php

namespace Database\Seeders;

use App\Core\Numbering\NumberingService;
use App\Models\Modules\Contractor\Contractor;
use Illuminate\Database\Seeder;

class ContractorSeeder extends Seeder
{
    public function run(): void
    {
        $numberingService = app(NumberingService::class);

        $contractors = [
            [
                'company_name' => 'PT Jaya Konstruksi',
                'contact_person' => 'Budi Santoso',
                'contact_phone' => '021-5551234',
                'contact_email' => 'budi@jayakonstruksi.com',
                'business_type' => 'construction',
                'contract_status' => 'active',
                'contract_start_date' => '2025-01-01',
                'contract_end_date' => '2026-12-31',
                'safety_induction_required' => true,
                'safety_induction_date' => '2025-01-15',
                'safety_induction_expiry' => '2026-01-15',
                'insurance_required' => true,
                'insurance_policy_number' => 'INS-2025-001',
                'insurance_expiry' => '2026-01-01',
                'approval_status' => 'approved',
                'approved_by' => 1,
                'approved_at' => now(),
            ],
            [
                'company_name' => 'CV Sukses Maintenance',
                'contact_person' => 'Siti Rahma',
                'contact_phone' => '021-5555678',
                'contact_email' => 'siti@suksesmaintenance.com',
                'business_type' => 'maintenance',
                'contract_status' => 'active',
                'contract_start_date' => '2025-06-01',
                'contract_end_date' => '2026-05-31',
                'safety_induction_required' => true,
                'safety_induction_date' => '2025-06-10',
                'safety_induction_expiry' => '2026-06-10',
                'insurance_required' => true,
                'insurance_policy_number' => 'INS-2025-002',
                'insurance_expiry' => '2026-06-01',
                'approval_status' => 'approved',
                'approved_by' => 1,
                'approved_at' => now(),
            ],
            [
                'company_name' => 'PT Bersih Sejahtera',
                'contact_person' => 'Ahmad Yani',
                'contact_phone' => '021-5559012',
                'business_type' => 'cleaning',
                'contract_status' => 'pending',
                'contract_start_date' => '2026-08-01',
                'contract_end_date' => '2027-07-31',
                'approval_status' => 'submitted',
            ],
        ];

        foreach ($contractors as $data) {
            $generated = $numberingService->generate(
                moduleName: 'contractor',
                actor: null,
                siteCode: null,
                referenceType: 'CON'
            );
            $data['contractor_number'] = $generated->number;
            $data['created_by'] = 1;
            $data['updated_by'] = 1;

            Contractor::create($data);
        }
    }
}
