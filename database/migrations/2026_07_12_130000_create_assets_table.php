<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('assets', function (Blueprint $table) {
            $table->id();
            
            // Unique asset number (AST-YYYY-NNNN)
            $table->string('asset_number', 50)->unique();
            
            // Basic Information
            $table->string('name');
            $table->enum('category', [
                'equipment',
                'machinery',
                'vehicle',
                'safety_equipment',
                'fire_equipment',
                'lifting',
                'other'
            ])->default('equipment');
            $table->string('serial_number')->nullable();
            $table->string('model')->nullable();
            $table->string('manufacturer')->nullable();
            
            // Location
            $table->foreignId('site_id')->constrained('sites')->cascadeOnDelete();
            $table->foreignId('area_id')->nullable()->constrained('areas')->nullOnDelete();
            $table->foreignId('department_id')->nullable()->constrained('departments')->nullOnDelete();
            
            // Dates
            $table->date('purchase_date')->nullable();
            $table->date('installation_date')->nullable();
            $table->date('warranty_expiry_date')->nullable();
            
            // Status & Safety
            $table->enum('status', ['active', 'inactive', 'decommissioned'])->default('active');
            $table->boolean('safety_critical')->default(false);
            
            // Next Inspection
            $table->date('next_inspection_date')->nullable();
            
            // Notes
            $table->text('description')->nullable();
            $table->text('notes')->nullable();
            
            // Audit fields
            $table->foreignId('created_by')->constrained('users');
            $table->foreignId('updated_by')->constrained('users');
            $table->timestamps();
            $table->softDeletes();
            
            // Indexes
            $table->index(['site_id', 'status']);
            $table->index(['category', 'status']);
            $table->index(['safety_critical', 'status']);
            $table->index('next_inspection_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('assets');
    }
};
