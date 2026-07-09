<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('numbering_formats', function (Blueprint $table) {
            $table->id();
            $table->string('module_name', 100)->unique();
            $table->string('prefix', 30);
            $table->unsignedTinyInteger('padding')->default(4);
            $table->string('separator', 5)->default('-');
            $table->string('reset_frequency', 20)->default('yearly');
            $table->boolean('include_year')->default(true);
            $table->boolean('include_site_code')->default(false);
            $table->string('sample')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('numbering_formats');
    }
};
