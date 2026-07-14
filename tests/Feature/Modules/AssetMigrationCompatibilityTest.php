<?php

use App\Models\Core\MasterData\Department;
use App\Models\Core\MasterData\Priority;
use App\Models\Core\MasterData\Site;
use App\Models\Modules\Asset\Asset;
use App\Models\Modules\Asset\AssetCertificate;
use App\Models\Modules\Asset\AssetInspection;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

it('preserves released asset compliance data across forward rollback and re-forward migrations', function () {
    $certificateMigration = require database_path('migrations/2026_07_14_120000_add_certificate_file_id_to_asset_certificates_table.php');
    $capaMigration = require database_path('migrations/2026_07_14_120100_move_asset_inspection_capa_link_to_source_columns.php');
    $historyMigration = require database_path('migrations/2026_07_14_120200_remove_soft_deletes_from_asset_tables.php');

    $historyMigration->down();
    $capaMigration->down();
    $certificateMigration->down();

    expect(Schema::hasColumn('asset_certificates', 'certificate_file_id'))->toBeFalse()
        ->and(Schema::hasColumn('asset_inspections', 'capa_action_id'))->toBeTrue()
        ->and(Schema::hasColumn('assets', 'deleted_at'))->toBeTrue();

    $user = User::factory()->create();
    $site = Site::factory()->create();
    $department = Department::factory()->for($site)->create();
    $priority = Priority::factory()->create();
    $deletedAt = now()->subDay()->startOfSecond();
    $now = now();

    $assetId = DB::table('assets')->insertGetId([
        'asset_number' => 'AST-UPGRADE-0001',
        'name' => 'Released asset',
        'category' => 'equipment',
        'site_id' => $site->id,
        'department_id' => $department->id,
        'status' => 'active',
        'safety_critical' => true,
        'created_by' => $user->id,
        'updated_by' => $user->id,
        'created_at' => $now,
        'updated_at' => $now,
        'deleted_at' => $deletedAt,
    ]);
    $certificateId = DB::table('asset_certificates')->insertGetId([
        'asset_id' => $assetId,
        'certificate_type' => 'Calibration',
        'certificate_number' => 'CERT-UPGRADE',
        'issuing_body' => 'Accredited Body',
        'issued_date' => today()->subYear(),
        'status' => 'valid',
        'created_by' => $user->id,
        'updated_by' => $user->id,
        'created_at' => $now,
        'updated_at' => $now,
        'deleted_at' => $deletedAt,
    ]);
    $fileId = DB::table('managed_files')->insertGetId([
        'module_name' => 'asset',
        'reference_id' => $assetId,
        'collection' => 'certificate',
        'disk' => 'local',
        'path' => 'managed-files/asset/upgrade/certificate.pdf',
        'original_name' => 'certificate.pdf',
        'stored_name' => 'certificate.pdf',
        'mime_type' => 'application/pdf',
        'extension' => 'pdf',
        'size' => 128,
        'metadata' => null,
        'uploaded_by' => $user->id,
        'created_at' => $now,
        'updated_at' => $now,
    ]);
    $capaId = DB::table('capa_actions')->insertGetId([
        'action_number' => 'CAPA-UPGRADE-0001',
        'title' => 'Released CAPA',
        'description' => 'Preserve provenance',
        'site_id' => $site->id,
        'department_id' => $department->id,
        'assigned_to' => $user->id,
        'assigned_by' => $user->id,
        'priority_id' => $priority->id,
        'status' => 'open',
        'created_at' => $now,
        'updated_at' => $now,
    ]);
    $inspectionId = DB::table('asset_inspections')->insertGetId([
        'asset_id' => $assetId,
        'inspection_date' => today(),
        'inspector_id' => $user->id,
        'result' => 'fail',
        'capa_action_id' => $capaId,
        'created_by' => $user->id,
        'updated_by' => $user->id,
        'created_at' => $now,
        'updated_at' => $now,
        'deleted_at' => $deletedAt,
    ]);

    $assertForwardState = function () use ($assetId, $certificateId, $fileId, $capaId, $inspectionId, $deletedAt): void {
        expect(DB::table('asset_certificates')->where('id', $certificateId)->value('certificate_file_id'))->toBe($fileId)
            ->and(DB::table('capa_actions')->where('id', $capaId)->value('source_module'))->toBe('asset_inspection')
            ->and(DB::table('capa_actions')->where('id', $capaId)->value('source_reference_id'))->toBe($inspectionId)
            ->and(DB::table('assets')->where('id', $assetId)->value('status'))->toBe('decommissioned')
            ->and((string) DB::table('assets')->where('id', $assetId)->value('legacy_deleted_at'))->toBe($deletedAt->format('Y-m-d H:i:s'))
            ->and((string) DB::table('asset_certificates')->where('id', $certificateId)->value('legacy_deleted_at'))->toBe($deletedAt->format('Y-m-d H:i:s'))
            ->and((string) DB::table('asset_inspections')->where('id', $inspectionId)->value('legacy_deleted_at'))->toBe($deletedAt->format('Y-m-d H:i:s'));
    };

    $certificateMigration->up();
    $capaMigration->up();
    $historyMigration->up();
    $assertForwardState();

    $historyMigration->down();
    $capaMigration->down();
    $certificateMigration->down();

    expect(DB::table('assets')->where('id', $assetId)->value('status'))->toBe('active')
        ->and((string) DB::table('assets')->where('id', $assetId)->value('deleted_at'))->toBe($deletedAt->format('Y-m-d H:i:s'))
        ->and(DB::table('asset_inspections')->where('id', $inspectionId)->value('capa_action_id'))->toBe($capaId)
        ->and(DB::table('capa_actions')->where('id', $capaId)->value('source_module'))->toBeNull()
        ->and(data_get(json_decode(DB::table('managed_files')->where('id', $fileId)->value('metadata'), true), 'certificate_id'))->toBe($certificateId);

    $certificateMigration->up();
    $capaMigration->up();
    $historyMigration->up();
    $assertForwardState();
});

it('fails before schema mutation when legacy certificate evidence is ambiguous', function () {
    $certificateMigration = require database_path('migrations/2026_07_14_120000_add_certificate_file_id_to_asset_certificates_table.php');
    $capaMigration = require database_path('migrations/2026_07_14_120100_move_asset_inspection_capa_link_to_source_columns.php');
    $historyMigration = require database_path('migrations/2026_07_14_120200_remove_soft_deletes_from_asset_tables.php');

    $historyMigration->down();
    $capaMigration->down();
    $certificateMigration->down();

    $user = User::factory()->create();
    $site = Site::factory()->create();
    $now = now();
    $assetId = DB::table('assets')->insertGetId([
        'asset_number' => 'AST-AMBIGUOUS-0001',
        'name' => 'Ambiguous evidence asset',
        'category' => 'equipment',
        'site_id' => $site->id,
        'status' => 'active',
        'safety_critical' => false,
        'created_by' => $user->id,
        'updated_by' => $user->id,
        'created_at' => $now,
        'updated_at' => $now,
    ]);

    foreach (['CERT-A', 'CERT-B'] as $number) {
        DB::table('asset_certificates')->insert([
            'asset_id' => $assetId,
            'certificate_type' => 'Calibration',
            'certificate_number' => $number,
            'status' => 'valid',
            'created_by' => $user->id,
            'updated_by' => $user->id,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }
    DB::table('managed_files')->insert([
        'module_name' => 'asset',
        'reference_id' => $assetId,
        'collection' => 'certificate',
        'disk' => 'local',
        'path' => 'managed-files/asset/ambiguous/certificate.pdf',
        'original_name' => 'certificate.pdf',
        'stored_name' => 'certificate.pdf',
        'mime_type' => 'application/pdf',
        'extension' => 'pdf',
        'size' => 128,
        'uploaded_by' => $user->id,
        'created_at' => $now,
        'updated_at' => $now,
    ]);

    expect(fn () => $certificateMigration->up())
        ->toThrow(RuntimeException::class, "Legacy certificate evidence for asset {$assetId} is ambiguous.");

    expect(Schema::hasColumn('asset_certificates', 'certificate_file_id'))->toBeFalse()
        ->and(DB::table('asset_certificates')->where('asset_id', $assetId)->count())->toBe(2)
        ->and(DB::table('managed_files')->where('reference_id', $assetId)->count())->toBe(1);
});

it('preserves compliance history while upgrading the site foreign key to restrict deletion', function () {
    $migration = require database_path('migrations/2026_07_14_120300_restrict_site_deletion_for_assets_table.php');
    $user = User::factory()->create();
    $site = Site::factory()->create();
    $asset = Asset::create([
        'asset_number' => 'AST-FK-UPGRADE-0001',
        'name' => 'Permanent compliance asset',
        'category' => 'equipment',
        'site_id' => $site->id,
        'status' => 'active',
        'created_by' => $user->id,
        'updated_by' => $user->id,
    ]);
    $certificate = AssetCertificate::create([
        'asset_id' => $asset->id,
        'certificate_type' => 'Calibration',
        'certificate_number' => 'CERT-FK-UPGRADE',
        'issued_date' => today(),
        'status' => 'valid',
        'created_by' => $user->id,
        'updated_by' => $user->id,
    ]);
    $inspection = AssetInspection::create([
        'asset_id' => $asset->id,
        'inspection_date' => today(),
        'inspector_id' => $user->id,
        'result' => 'pass',
        'created_by' => $user->id,
        'updated_by' => $user->id,
    ]);

    $migration->down();

    expect($asset->fresh())->not->toBeNull()
        ->and($certificate->fresh())->not->toBeNull()
        ->and($inspection->fresh())->not->toBeNull();

    $migration->up();

    expect($asset->fresh())->not->toBeNull()
        ->and($certificate->fresh())->not->toBeNull()
        ->and($inspection->fresh())->not->toBeNull()
        ->and(fn () => $site->delete())->toThrow(QueryException::class);
});
