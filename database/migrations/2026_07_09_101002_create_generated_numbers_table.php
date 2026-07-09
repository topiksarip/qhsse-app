<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('generated_numbers', function (Blueprint $table) {
            $table->id();
            $table->string('module_name', 100);
            $table->string('number')->unique();
            $table->string('site_code', 50)->default('');
            $table->unsignedSmallInteger('year')->nullable();
            $table->unsignedBigInteger('sequence');
            $table->string('reference_type')->nullable();
            $table->unsignedBigInteger('reference_id')->nullable();
            $table->foreignId('generated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['module_name', 'site_code', 'year']);
            $table->index(['reference_type', 'reference_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('generated_numbers');
    }
};
