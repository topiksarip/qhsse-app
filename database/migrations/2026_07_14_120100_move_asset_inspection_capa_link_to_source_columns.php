<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $links = DB::table('asset_inspections')
            ->whereNotNull('capa_action_id')
            ->orderBy('id')
            ->get(['id', 'capa_action_id']);

        $duplicateActionId = $links
            ->groupBy('capa_action_id')
            ->first(fn ($group) => $group->count() > 1)?->first()?->capa_action_id;

        if ($duplicateActionId !== null) {
            throw new RuntimeException("CAPA action {$duplicateActionId} is linked to multiple asset inspections.");
        }

        foreach ($links as $inspection) {
            $action = DB::table('capa_actions')->where('id', $inspection->capa_action_id)->first();

            if ($action === null || $action->source_module !== null || $action->source_reference_id !== null) {
                throw new RuntimeException("CAPA action {$inspection->capa_action_id} has existing source provenance.");
            }
        }

        foreach ($links as $inspection) {
            DB::table('capa_actions')
                ->where('id', $inspection->capa_action_id)
                ->update([
                    'source_module' => 'asset_inspection',
                    'source_reference_id' => $inspection->id,
                ]);
        }

        Schema::table('asset_inspections', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('capa_action_id');
        });
    }

    public function down(): void
    {
        Schema::table('asset_inspections', function (Blueprint $table): void {
            $table->foreignId('capa_action_id')
                ->nullable()
                ->constrained('capa_actions')
                ->nullOnDelete();
        });

        DB::table('capa_actions')
            ->where('source_module', 'asset_inspection')
            ->whereNotNull('source_reference_id')
            ->orderBy('id')
            ->each(function (object $action): void {
                DB::table('asset_inspections')
                    ->where('id', $action->source_reference_id)
                    ->update(['capa_action_id' => $action->id]);
            });

        DB::table('capa_actions')
            ->where('source_module', 'asset_inspection')
            ->update([
                'source_module' => null,
                'source_reference_id' => null,
            ]);
    }
};
