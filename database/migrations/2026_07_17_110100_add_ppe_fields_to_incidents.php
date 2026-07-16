<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('incidents', function (Blueprint $table) {
            $table->boolean('ppe_involved')->default(false)->after('immediate_action');
            $table->unsignedBigInteger('apd_item_id')->nullable()->after('ppe_involved');
            $table->boolean('ppe_failure')->default(false)->after('apd_item_id');
            $table->text('ppe_notes')->nullable()->after('ppe_failure');

            $table->foreign('apd_item_id')->references('id')->on('apd_items')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::table('incidents', function (Blueprint $table) {
            $table->dropForeign(['apd_item_id']);
            $table->dropColumn(['ppe_involved', 'apd_item_id', 'ppe_failure', 'ppe_notes']);
        });
    }
};
