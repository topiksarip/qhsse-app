<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('environmental_records', function (Blueprint $table) {
            $table->id();
            $table->string('record_number', 50)->unique();
            $table->string('type', 50);
            $table->string('title');
            $table->text('description');
            $table->foreignId('site_id')->constrained()->restrictOnDelete();
            $table->foreignId('area_id')->nullable()->constrained()->restrictOnDelete();
            $table->timestamp('occurred_at')->nullable();
            
            // Measurement fields (emission, noise, water_monitoring)
            $table->decimal('measured_value', 15, 4)->nullable();
            $table->string('unit', 50)->nullable();
            $table->decimal('limit_value', 15, 4)->nullable();
            $table->boolean('is_exceedance')->default(false);
            
            // Type-specific: waste
            $table->string('waste_type')->nullable();
            $table->decimal('quantity', 15, 4)->nullable();
            $table->string('disposal_method')->nullable();
            
            // Type-specific: spill
            $table->string('material')->nullable();
            $table->decimal('volume', 15, 4)->nullable();
            $table->string('containment')->nullable();
            
            // Type-specific: emission/water_monitoring
            $table->string('parameter')->nullable();
            
            // Type-specific: noise
            $table->string('location')->nullable();
            
            $table->foreignId('reporter_id')->constrained('users')->restrictOnDelete();
            $table->string('status', 50)->default('recorded');
            $table->foreignId('capa_action_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamps();

            // Indexes
            $table->index('site_id');
            $table->index('area_id');
            $table->index('type');
            $table->index('status');
            $table->index('is_exceedance');
            $table->index('occurred_at');
            $table->index('reporter_id');
            $table->index('capa_action_id');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('environmental_records');
    }
};
