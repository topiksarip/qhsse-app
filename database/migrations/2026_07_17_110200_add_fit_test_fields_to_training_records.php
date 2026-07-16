<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('training_records', function (Blueprint $table) {
            $table->string('training_type')->nullable()->after('training_program_id');
            $table->unsignedBigInteger('apd_item_id')->nullable()->after('training_type');
            $table->string('fit_test_result')->nullable()->after('apd_item_id');

            $table->foreign('apd_item_id')->references('id')->on('apd_items')->onDelete('set null');
            $table->index(['training_type']);
        });
    }

    public function down(): void
    {
        Schema::table('training_records', function (Blueprint $table) {
            $table->dropForeign(['apd_item_id']);
            $table->dropColumn(['training_type', 'apd_item_id', 'fit_test_result']);
        });
    }
};
