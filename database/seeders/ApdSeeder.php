<?php

namespace Database\Seeders;

use App\Core\Numbering\NumberingService;
use App\Models\Core\MasterData\Area;
use App\Models\Core\MasterData\Department;
use App\Models\Core\MasterData\Site;
use App\Models\Modules\Apd\ApdCatalog;
use App\Models\Modules\Apd\ApdItem;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ApdSeeder extends Seeder
{
    public function run(): void
    {
        $numberingService = app(NumberingService::class);

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
        if (! $user) {
            return;
        }

        DB::beginTransaction();

        try {
            $catalogs = [
                [
                    'name' => 'Helm Safety Petrolium',
                    'category' => 'head_protection',
                    'track_type' => 'serial',
                    'manufacturer' => '3M',
                    'model' => 'H-700',
                    'standard' => 'SNI 1041:2008',
                    'protection_level' => 'high',
                    'default_lifespan_months' => 60,
                    'inspection_interval_days' => 90,
                    'min_stock' => 5,
                    'reorder_point' => 10,
                ],
                [
                    'name' => 'Sepatu Safety Composite',
                    'category' => 'foot_protection',
                    'track_type' => 'serial',
                    'manufacturer' => 'Red Wing',
                    'model' => 'King Toe',
                    'standard' => 'EN ISO 20345',
                    'protection_level' => 'medium',
                    'default_lifespan_months' => 36,
                    'inspection_interval_days' => 180,
                    'min_stock' => 8,
                    'reorder_point' => 15,
                ],
                [
                    'name' => 'Sarung Tangan Cut Resistant',
                    'category' => 'hand_protection',
                    'track_type' => 'batch',
                    'manufacturer' => 'Ansell',
                    'model' => 'HyFlex 11-818',
                    'standard' => 'EN 388',
                    'protection_level' => 'medium',
                    'default_lifespan_months' => 12,
                    'inspection_interval_days' => 30,
                    'min_stock' => 50,
                    'reorder_point' => 100,
                ],
            ];

            $created = [];
            foreach ($catalogs as $c) {
                $created[] = ApdCatalog::create(array_merge([
                    'catalog_code' => $numberingService->generate(moduleName: 'apd', actor: $user, siteCode: $site->code ?? null, referenceType: 'catalog')->number,
                    'created_by' => $user->id,
                    'updated_by' => $user->id,
                    'is_active' => true,
                ], $c));
            }

            // Receive a few stock items for the first catalog.
            $serialCatalog = $created[0];
            foreach (['SN-APD-001', 'SN-APD-002', 'SN-APD-003'] as $serial) {
                ApdItem::create([
                    'item_number' => $numberingService->generate(moduleName: 'apd', actor: $user, siteCode: $site->code ?? null, referenceType: 'item')->number,
                    'catalog_id' => $serialCatalog->id,
                    'track_type' => 'serial',
                    'serial_number' => $serial,
                    'quantity' => 1,
                    'status' => 'in_stock',
                    'condition' => 'new',
                    'site_id' => $site->id,
                    'area_id' => $area->id,
                    'department_id' => $department->id,
                    'received_date' => now()->toDateString(),
                    'created_by' => $user->id,
                    'updated_by' => $user->id,
                ]);
            }

            // Batch receive for the glove catalog.
            $batchCatalog = $created[2];
            $batchItem = ApdItem::create([
                'item_number' => $numberingService->generate(moduleName: 'apd', actor: $user, siteCode: $site->code ?? null, referenceType: 'item')->number,
                'catalog_id' => $batchCatalog->id,
                'track_type' => 'batch',
                'quantity' => 60,
                'status' => 'in_stock',
                'condition' => 'new',
                'site_id' => $site->id,
                'department_id' => $department->id,
                'received_date' => now()->toDateString(),
                'created_by' => $user->id,
                'updated_by' => $user->id,
            ]);

            // Demo issuance: issue one serial helmet to the first employee (if any).
            $employee = \App\Models\Core\Users\Employee::first();
            if ($employee) {
                $firstSerial = ApdItem::where('catalog_id', $serialCatalog->id)
                    ->where('status', 'in_stock')
                    ->first();

                if ($firstSerial) {
                    $issuance = app(\App\Modules\Apd\ApdLifecycle::class)->create([
                        'apd_item_id' => $firstSerial->id,
                        'holder_type' => 'employee',
                        'holder_id' => $employee->id,
                        'quantity' => 1,
                        'condition_out' => 'new',
                        'issue_date' => now()->toDateString(),
                    ], $user);

                    $this->command->info('✓ Demo issuance (serial): ' . $issuance->issue_number);
                }

                // Demo issuance: issue a batch lot of gloves to a location (area).
                $lotIssuance = app(\App\Modules\Apd\ApdLifecycle::class)->create([
                    'apd_item_id' => $batchItem->id,
                    'holder_type' => 'location',
                    'holder_id' => $area->id,
                    'quantity' => 10,
                    'condition_out' => 'new',
                    'issue_date' => now()->toDateString(),
                ], $user);

                $this->command->info('✓ Demo issuance (lot): ' . $lotIssuance->issue_number);
            }

            DB::commit();

            $this->command->info('✓ Seeded APD catalogs and stock items');
        } catch (\Exception $e) {
            DB::rollBack();
            $this->command->error('✗ APD seeding failed: ' . $e->getMessage());
            throw $e;
        }
    }
}
