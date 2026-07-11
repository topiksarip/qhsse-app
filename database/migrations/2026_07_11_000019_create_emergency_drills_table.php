<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('emergency_drills', function (Blueprint $table) {
            $table->id();
            $table->string('drill_number', 50)->unique();
            $table->foreignId('emergency_plan_id')->constrained('emergency_plans');
            $table->date('scheduled_date');
            $table->date('executed_date')->nullable();
            $table->foreignId('site_id')->constrained('sites');
            $table->integer('participants_count')->nullable();
            $table->foreignId('observer_id')->constrained('users');
            $table->string('result', 50)->nullable();
            $table->text('findings')->nullable();
            $table->text('recommendations')->nullable();
            $table->string('status', 50)->default('scheduled');
            $table->timestamps();

            // Indexes
            $table->index('emergency_plan_id');
            $table->index('site_id');
            $table->index('observer_id');
            $table->index('scheduled_date');
            $table->index('executed_date');
            $table->index('status');
            $table->index('result');
        });

        // PostgreSQL check constraints
        if (DB::connection()->getDriverName() === 'pgsql') {
            DB::statement("
                ALTER TABLE emergency_drills ADD CONSTRAINT emergency_drills_result_check 
                CHECK (result IS NULL OR result IN ('pass','fail','needs_improvement'))
            ");
            DB::statement("
                ALTER TABLE emergency_drills ADD CONSTRAINT emergency_drills_status_check 
                CHECK (status IN ('scheduled','executed'))
            ");
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('emergency_drills');
    }
};
