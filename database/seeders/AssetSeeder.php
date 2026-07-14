<?php

namespace Database\Seeders;

use App\Core\Numbering\NumberingService;
use App\Models\Core\MasterData\Area;
use App\Models\Core\MasterData\Department;
use App\Models\Core\MasterData\Site;
use App\Models\Modules\Asset\Asset;
use App\Models\Modules\Asset\AssetCertificate;
use App\Models\Modules\Asset\AssetInspection;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class AssetSeeder extends Seeder
{
    public function run(): void
    {
        $numberingService = app(NumberingService::class);

        // Create minimal org data if not exists
        $site = Site::firstOrCreate(
            ['code' => 'HQ'],
            ['name' => 'Headquarters', 'address' => 'Test Address', 'is_active' => true]
        );

        $area = Area::where('site_id', $site->id)->first();
        if (! $area) {
            $area = Area::create([
                'site_id' => $site->id,
                'code' => 'PROD',
                'name' => 'Production Area',
                'is_active' => true,
            ]);
        }

        $department = Department::firstOrCreate(
            ['site_id' => $site->id, 'code' => 'OPS'],
            ['name' => 'Operations', 'is_active' => true]
        );

        $user = User::first();

        DB::beginTransaction();

        try {
            // Asset 1: Safety-critical equipment
            $asset1 = Asset::create([
                'asset_number' => $numberingService->generate(moduleName: 'asset', actor: $user, siteCode: $site->code ?? null, referenceType: 'asset')->number,
                'name' => 'High Pressure Compressor Unit',
                'category' => 'machinery',
                'created_by' => $user->id,
                'updated_by' => $user->id,
                'serial_number' => 'HPC-2024-001',
                'model' => 'Atlas Copco GA90',
                'manufacturer' => 'Atlas Copco',
                'status' => 'active',
                'safety_critical' => true,
                'purchase_date' => '2023-01-15',
                'installation_date' => '2023-02-01',
                'warranty_expiry_date' => '2026-02-01',
                'next_inspection_date' => '2026-08-01',
                'site_id' => $site->id,
                'area_id' => $area->id,
                'department_id' => $department->id,
                'description' => 'Primary air compressor for production line',
                'notes' => 'Requires monthly maintenance and quarterly safety inspections',
            ]);

            // Certificate for Asset 1
            AssetCertificate::create([
                'asset_id' => $asset1->id,
                'certificate_type' => 'pressure_vessel',
                'certificate_number' => 'PV-2024-HPC-001',
                'issued_date' => '2024-01-10',
                'expiry_date' => '2026-01-10',
                'issuing_body' => 'National Board of Boiler and Pressure Vessel Inspectors',
                'notes' => 'Annual re-certification required',
                'created_by' => $user->id,
                'updated_by' => $user->id,
            ]);

            // Inspection for Asset 1
            AssetInspection::create([
                'asset_id' => $asset1->id,
                'inspection_date' => '2026-06-15',
                'result' => 'pass',
                'inspector_id' => $user->id,
                'findings' => 'All safety systems operational. Pressure relief valve tested and functional.',
                'notes' => 'Continue regular maintenance schedule. Replace air filters within 2 months.',
                'next_inspection_date' => '2026-12-15',
                'created_by' => $user->id,
                'updated_by' => $user->id,
            ]);

            // Asset 2: Regular equipment
            $asset2 = Asset::create([
                'asset_number' => $numberingService->generate(moduleName: 'asset', actor: $user, siteCode: $site->code ?? null, referenceType: 'asset')->number,
                'name' => 'Forklift - Electric Model',
                'category' => 'vehicle',
                'created_by' => $user->id,
                'updated_by' => $user->id,
                'serial_number' => 'FL-2025-012',
                'model' => 'Toyota 8FBCU25',
                'manufacturer' => 'Toyota Material Handling',
                'status' => 'active',
                'safety_critical' => false,
                'purchase_date' => '2025-03-10',
                'installation_date' => '2025-03-15',
                'next_inspection_date' => '2026-09-15',
                'site_id' => $site->id,
                'department_id' => $department->id,
                'description' => 'Material handling equipment for warehouse operations',
            ]);

            // Asset 3: Testing equipment
            $asset3 = Asset::create([
                'asset_number' => $numberingService->generate(moduleName: 'asset', actor: $user, siteCode: $site->code ?? null, referenceType: 'asset')->number,
                'name' => 'Portable Gas Detector',
                'category' => 'safety_equipment',
                'created_by' => $user->id,
                'updated_by' => $user->id,
                'serial_number' => 'GD-2024-078',
                'model' => 'BW Technologies GasAlertMax XT II',
                'manufacturer' => 'Honeywell',
                'status' => 'active',
                'safety_critical' => true,
                'purchase_date' => '2024-06-20',
                'warranty_expiry_date' => '2027-06-20',
                'next_inspection_date' => '2026-12-20',
                'site_id' => $site->id,
                'area_id' => $area->id,
                'description' => 'Multi-gas detector for confined space entry',
                'notes' => 'Calibration required every 6 months',
            ]);

            // Certificate for Asset 3
            AssetCertificate::create([
                'asset_id' => $asset3->id,
                'certificate_type' => 'calibration',
                'certificate_number' => 'CAL-2026-GD-078',
                'issued_date' => '2026-06-20',
                'expiry_date' => '2026-12-20',
                'issuing_body' => 'Honeywell Calibration Services',
                'notes' => 'Calibrated for O2, LEL, H2S, CO detection',
                'created_by' => $user->id,
                'updated_by' => $user->id,
            ]);

            DB::commit();

            $this->command->info('✓ Seeded 3 assets with certificates and inspections');
        } catch (\Exception $e) {
            DB::rollBack();
            $this->command->error('✗ Asset seeding failed: '.$e->getMessage());
            throw $e;
        }
    }
}
