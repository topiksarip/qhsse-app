<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Convert role columns to TEXT so they can store JSON arrays of multiple roles.
        // Drop + recreate (no doctrine/dbal dependency). Existing data is preserved via temp copy.
        Schema::table('permit_assets', function (Blueprint $table) {
            $table->dropColumn('role');
        });
        Schema::table('permit_assets', function (Blueprint $table) {
            $table->text('role')->nullable()->comment('Peran alat dalam izin kerja (JSON array, opsional)')->after('asset_id');
        });

        Schema::table('permit_workers', function (Blueprint $table) {
            $table->dropColumn('role');
        });
        Schema::table('permit_workers', function (Blueprint $table) {
            $table->text('role')->nullable()->comment('Peran pekerja dalam izin kerja (JSON array, mis. operator, pengawas)')->after('employee_id');
        });
    }

    public function down(): void
    {
        Schema::table('permit_assets', function (Blueprint $table) {
            $table->dropColumn('role');
        });
        Schema::table('permit_assets', function (Blueprint $table) {
            $table->string('role')->nullable()->comment('Peran alat dalam izin kerja (opsional)')->after('asset_id');
        });

        Schema::table('permit_workers', function (Blueprint $table) {
            $table->dropColumn('role');
        });
        Schema::table('permit_workers', function (Blueprint $table) {
            $table->string('role')->nullable()->comment('Peran pekerja dalam izin kerja (mis. operator, pengawas)')->after('employee_id');
        });
    }
};
