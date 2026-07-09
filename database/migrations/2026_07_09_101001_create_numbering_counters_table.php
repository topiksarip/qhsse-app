<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('numbering_counters', function (Blueprint $table) {
            $table->id();
            $table->string('module_name', 100);
            $table->string('site_code', 50)->default('');
            $table->unsignedSmallInteger('year')->nullable();
            $table->unsignedBigInteger('current_number')->default(0);
            $table->timestamps();

            $table->unique(['module_name', 'site_code', 'year']);
            $table->index(['module_name', 'year']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('numbering_counters');
    }
};
