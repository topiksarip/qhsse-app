<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('risk_registers', function (Blueprint $table): void {
            $table->id();
            $table->string('register_number', 50)->unique();
            $table->string('title', 255);
            $table->string('type', 50);
            $table->foreignId('site_id')->constrained('sites')->restrictOnDelete();
            $table->foreignId('area_id')->nullable()->constrained('areas')->nullOnDelete();
            $table->foreignId('department_id')->nullable()->constrained('departments')->nullOnDelete();
            $table->string('activity', 500);
            $table->text('hazard');
            $table->text('existing_controls')->nullable();
            $table->foreignId('severity_id')->nullable()->constrained('severities')->nullOnDelete();
            $table->integer('probability_id')->nullable();
            $table->foreignId('risk_level_id')->nullable()->constrained('risk_matrix_levels')->nullOnDelete();
            $table->text('additional_controls')->nullable();
            $table->foreignId('residual_severity_id')->nullable()->constrained('severities')->nullOnDelete();
            $table->integer('residual_probability_id')->nullable();
            $table->foreignId('residual_risk_level_id')->nullable()->constrained('risk_matrix_levels')->nullOnDelete();
            $table->foreignId('owner_id')->constrained('users')->restrictOnDelete();
            $table->string('status', 50)->default('identified');
            $table->date('review_date')->nullable();
            $table->timestamps();

            // Indexes
            $table->index('site_id');
            $table->index('area_id');
            $table->index('department_id');
            $table->index('type');
            $table->index('status');
            $table->index('severity_id');
            $table->index('risk_level_id');
            $table->index('owner_id');
            $table->index('review_date');
            $table->index('created_at');
        });

        // PostgreSQL check constraints
        if (DB::connection()->getDriverName() === 'pgsql') {
            DB::statement("
                ALTER TABLE risk_registers ADD CONSTRAINT risk_registers_type_check 
                CHECK (type IN ('hazard_identification','jsa','hiradc','risk_assessment'))
            ");
            DB::statement("
                ALTER TABLE risk_registers ADD CONSTRAINT risk_registers_status_check 
                CHECK (status IN ('identified','assessed','controls_needed','controls_in_place','monitored','obsolete'))
            ");
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('risk_registers');
    }
};
