<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Rename conflicting string columns 'department' and 'position' to '_legacy' suffix
     * so they don't conflict with BelongsTo relation methods of the same name.
     */
    public function up(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            // Only rename if columns exist (idempotent)
            if (Schema::hasColumn('employees', 'department') && ! Schema::hasColumn('employees', 'department_legacy')) {
                $table->renameColumn('department', 'department_legacy');
            }

            if (Schema::hasColumn('employees', 'position') && ! Schema::hasColumn('employees', 'position_legacy')) {
                $table->renameColumn('position', 'position_legacy');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            if (Schema::hasColumn('employees', 'department_legacy') && ! Schema::hasColumn('employees', 'department')) {
                $table->renameColumn('department_legacy', 'department');
            }

            if (Schema::hasColumn('employees', 'position_legacy') && ! Schema::hasColumn('employees', 'position')) {
                $table->renameColumn('position_legacy', 'position');
            }
        });
    }
};
