<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inspection_units', function (Blueprint $table) {
            $table->id();
            $table->foreignId('inspection_id')->constrained('inspections')->cascadeOnDelete();
            $table->string('identifier');
            $table->string('status')->default('pending'); // pending | done | cancelled
            $table->text('notes')->nullable();
            $table->text('cancelled_reason')->nullable();
            $table->timestamps();
            $table->index('inspection_id');
            $table->index('status');
        });

        Schema::table('inspection_results', function (Blueprint $table) {
            $table->foreignId('inspection_unit_id')->nullable()->after('inspection_item_id')
                ->constrained('inspection_units')->nullOnDelete();
        });

        // Backfill legacy inspections: create 1 default unit each, link existing results.
        $inspections = DB::table('inspections')->get();
        foreach ($inspections as $insp) {
            $hasResults = DB::table('inspection_results')->where('inspection_id', $insp->id)->exists();
            $unitId = DB::table('inspection_units')->insertGetId([
                'inspection_id' => $insp->id,
                'identifier' => $insp->inspection_number,
                'status' => $hasResults ? 'done' : 'pending',
                'notes' => null,
                'cancelled_reason' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            DB::table('inspection_results')
                ->where('inspection_id', $insp->id)
                ->whereNull('inspection_unit_id')
                ->update(['inspection_unit_id' => $unitId]);
        }

        // Composite unique: one answer per (inspection, unit, item).
        Schema::table('inspection_results', function (Blueprint $table) {
            $table->unique(['inspection_id', 'inspection_unit_id', 'inspection_item_id'], 'inspection_results_unique');
        });
    }

    public function down(): void
    {
        Schema::table('inspection_results', function (Blueprint $table) {
            $table->dropUnique('inspection_results_unique');
            $table->dropForeign(['inspection_unit_id']);
            $table->dropColumn('inspection_unit_id');
        });
        Schema::dropIfExists('inspection_units');
    }
};
