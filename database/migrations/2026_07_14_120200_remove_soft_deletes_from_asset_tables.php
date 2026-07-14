<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('assets', 'deleted_at')) {
            Schema::table('assets', function (Blueprint $table): void {
                $table->timestamp('legacy_deleted_at')->nullable();
                $table->string('legacy_status_before_deletion')->nullable();
            });

            DB::table('assets')
                ->whereNotNull('deleted_at')
                ->update([
                    'legacy_deleted_at' => DB::raw('deleted_at'),
                    'legacy_status_before_deletion' => DB::raw('status'),
                    'status' => 'decommissioned',
                ]);
        }

        foreach (['asset_certificates', 'asset_inspections'] as $tableName) {
            if (Schema::hasColumn($tableName, 'deleted_at')) {
                Schema::table($tableName, function (Blueprint $table): void {
                    $table->timestamp('legacy_deleted_at')->nullable();
                });

                DB::table($tableName)
                    ->whereNotNull('deleted_at')
                    ->update(['legacy_deleted_at' => DB::raw('deleted_at')]);
            }
        }

        foreach (['asset_inspections', 'asset_certificates', 'assets'] as $tableName) {
            if (Schema::hasColumn($tableName, 'deleted_at')) {
                Schema::table($tableName, function (Blueprint $table): void {
                    $table->dropSoftDeletes();
                });
            }
        }
    }

    public function down(): void
    {
        foreach (['assets', 'asset_certificates', 'asset_inspections'] as $tableName) {
            if (! Schema::hasColumn($tableName, 'deleted_at')) {
                Schema::table($tableName, function (Blueprint $table): void {
                    $table->softDeletes();
                });
            }
        }

        foreach (['assets', 'asset_certificates', 'asset_inspections'] as $tableName) {
            if (Schema::hasColumn($tableName, 'legacy_deleted_at')) {
                DB::table($tableName)
                    ->whereNotNull('legacy_deleted_at')
                    ->update(['deleted_at' => DB::raw('legacy_deleted_at')]);
            }
        }

        if (Schema::hasColumn('assets', 'legacy_status_before_deletion')) {
            DB::table('assets')
                ->whereNotNull('legacy_status_before_deletion')
                ->update(['status' => DB::raw('legacy_status_before_deletion')]);
        }

        foreach (['asset_inspections', 'asset_certificates', 'assets'] as $tableName) {
            Schema::table($tableName, function (Blueprint $table) use ($tableName): void {
                $columns = ['legacy_deleted_at'];

                if ($tableName === 'assets') {
                    $columns[] = 'legacy_status_before_deletion';
                }

                $table->dropColumn($columns);
            });
        }
    }
};
