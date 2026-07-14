<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->replaceSiteForeignKey('restrict');
    }

    public function down(): void
    {
        $this->replaceSiteForeignKey('cascade');
    }

    private function replaceSiteForeignKey(string $deleteAction): void
    {
        $replace = function () use ($deleteAction): void {
            Schema::table('assets', function (Blueprint $table) use ($deleteAction): void {
                $table->dropForeign(['site_id']);
                $table->foreign('site_id')
                    ->references('id')
                    ->on('sites')
                    ->onDelete($deleteAction);
            });
        };

        if (DB::getDriverName() === 'sqlite') {
            $complianceRows = collect(['asset_certificates', 'asset_inspections'])
                ->filter(fn (string $table): bool => Schema::hasTable($table))
                ->mapWithKeys(fn (string $table): array => [
                    $table => DB::table($table)->get()->map(fn (object $row): array => (array) $row)->all(),
                ]);

            Schema::withoutForeignKeyConstraints($replace);

            $complianceRows->each(function (array $rows, string $table): void {
                foreach (array_chunk($rows, 50) as $chunk) {
                    DB::table($table)->insertOrIgnore($chunk);
                }
            });

            return;
        }

        $replace();
    }
};
