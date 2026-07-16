<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('apd_inspections', function (Blueprint $table) {
            $table->id();

            $table->foreignId('apd_item_id')->constrained('apd_items')->cascadeOnDelete();
            $table->string('inspection_type', 20)->comment('scheduled|incidental|manual');
            $table->foreignId('inspected_by')->constrained('users');
            $table->date('inspection_date');
            $table->string('result', 20)->comment('layak|tidak_layak');
            $table->string('condition', 20)->nullable()->comment('new|good|fair|poor (item condition snapshot)');
            $table->date('next_inspection_date')->nullable();
            $table->text('notes')->nullable();

            $table->foreignId('created_by')->constrained('users');
            $table->foreignId('updated_by')->constrained('users');
            $table->timestamps();
            $table->softDeletes();

            $table->index(['apd_item_id', 'inspection_date'], 'apd_inspections_item_date_index');
            $table->index(['result', 'created_at'], 'apd_inspections_result_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('apd_inspections');
    }
};
