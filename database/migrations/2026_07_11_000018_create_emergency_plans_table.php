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
        Schema::create('emergency_plans', function (Blueprint $table) {
            $table->id();
            $table->string('plan_number', 50)->unique();
            $table->string('name', 255);
            $table->string('type', 50);
            $table->foreignId('site_id')->constrained('sites');
            $table->text('description');
            $table->text('response_procedure');
            $table->text('escalation_procedure');
            $table->foreignId('contact_person_id')->constrained('users');
            $table->json('emergency_contacts')->nullable();
            $table->text('equipment_needed')->nullable();
            $table->timestamps();

            // Indexes
            $table->index('site_id');
            $table->index('type');
            $table->index('contact_person_id');
            $table->index('created_at');
        });

        // PostgreSQL check constraint
        if (DB::connection()->getDriverName() === 'pgsql') {
            DB::statement("
                ALTER TABLE emergency_plans ADD CONSTRAINT emergency_plans_type_check 
                CHECK (type IN ('fire','medical','spill','evacuation','natural_disaster','security','other'))
            ");
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('emergency_plans');
    }
};
