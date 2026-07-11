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
        Schema::create('security_incidents', function (Blueprint $table) {
            $table->id();
            $table->string('security_number', 50)->unique();
            $table->string('type', 50);
            $table->string('title');
            $table->text('description');
            $table->foreignId('site_id')->constrained()->restrictOnDelete();
            $table->foreignId('area_id')->nullable()->constrained()->restrictOnDelete();
            $table->timestamp('occurred_at');
            $table->foreignId('reported_by')->constrained('users')->restrictOnDelete();
            $table->foreignId('severity_id')->constrained()->restrictOnDelete();
            $table->string('status', 50)->default('reported');
            $table->text('resolution')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->timestamps();

            // Indexes
            $table->index('site_id');
            $table->index('area_id');
            $table->index('type');
            $table->index('status');
            $table->index('occurred_at');
            $table->index('reported_by');
            $table->index('severity_id');
            $table->index('created_at');
        });

        Schema::create('visitor_logs', function (Blueprint $table) {
            $table->id();
            $table->string('visitor_name');
            $table->string('visitor_type', 50);
            $table->string('visitor_id_number')->nullable();
            $table->string('visitor_company')->nullable();
            $table->string('visitor_phone', 20)->nullable();
            $table->foreignId('host_employee_id')->constrained('employees')->restrictOnDelete();
            $table->foreignId('site_id')->constrained()->restrictOnDelete();
            $table->string('purpose');
            $table->string('vehicle_number', 20)->nullable();
            $table->timestamp('checked_in_at');
            $table->timestamp('checked_out_at')->nullable();
            $table->foreignId('checked_in_by')->constrained('users')->restrictOnDelete();
            $table->foreignId('checked_out_by')->nullable()->constrained('users')->restrictOnDelete();
            $table->string('status', 50)->default('checked_in');
            $table->text('notes')->nullable();
            $table->timestamps();

            // Indexes
            $table->index('site_id');
            $table->index('host_employee_id');
            $table->index('visitor_type');
            $table->index('status');
            $table->index('checked_in_at');
            $table->index('checked_out_at');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('visitor_logs');
        Schema::dropIfExists('security_incidents');
    }
};
