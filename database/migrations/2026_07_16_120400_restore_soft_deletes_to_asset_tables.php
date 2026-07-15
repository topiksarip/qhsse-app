<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Restore SoftDeletes on asset tables so Asset / Certificate / Inspection
     * records can be soft-deleted (full CRUD) while preserving audit history.
     * Previously removed in 2026_07_14_120200; now re-enabled per full-CRUD requirement.
     */
    public function up(): void
    {
        foreach (['assets', 'asset_certificates', 'asset_inspections'] as $tableName) {
            if (! Schema::hasColumn($tableName, 'deleted_at')) {
                Schema::table($tableName, function (Blueprint $table): void {
                    $table->softDeletes();
                });
            }
        }
    }

    public function down(): void
    {
        foreach (['asset_inspections', 'asset_certificates', 'assets'] as $tableName) {
            if (Schema::hasColumn($tableName, 'deleted_at')) {
                Schema::table($tableName, function (Blueprint $table): void {
                    $table->dropSoftDeletes();
                });
            }
        }
    }
};
