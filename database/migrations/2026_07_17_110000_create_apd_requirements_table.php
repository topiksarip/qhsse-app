<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('apd_requirements', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('risk_register_id');
            $table->unsignedBigInteger('apd_catalog_id');
            $table->integer('quantity')->default(1);
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('risk_register_id')->references('id')->on('risk_registers')->onDelete('cascade');
            $table->foreign('apd_catalog_id')->references('id')->on('apd_catalogs')->onDelete('cascade');
            $table->index(['risk_register_id', 'apd_catalog_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('apd_requirements');
    }
};
