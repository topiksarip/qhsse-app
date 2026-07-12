<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('report_templates', function (Blueprint $table) {
            $table->id();
            
            // Template identification
            $table->string('name');
            $table->string('type'); // incident_summary, capa_summary, inspection_summary, audit_summary, training_compliance, monthly_qhsse, annual_qhsse, custom
            $table->text('description')->nullable();
            
            // Configuration (JSON)
            $table->json('config')->nullable(); // sections, data_sources, default_parameters
            
            // Status
            $table->boolean('is_active')->default(true);
            $table->boolean('is_predefined')->default(false); // true for seeded templates, false for custom
            
            // Audit fields
            $table->foreignId('created_by')->constrained('users');
            $table->foreignId('updated_by')->constrained('users');
            $table->timestamps();
            $table->softDeletes();
            
            // Indexes
            $table->index('type');
            $table->index('is_active');
            $table->index('is_predefined');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('report_templates');
    }
};
