<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('asset_certificates', function (Blueprint $table) {
            $table->id();
            
            // Foreign key to asset
            $table->foreignId('asset_id')->constrained('assets')->cascadeOnDelete();
            
            // Certificate details
            $table->string('certificate_type'); // e.g., "Safety Inspection", "Calibration", "Permit"
            $table->string('certificate_number')->nullable();
            $table->string('issuing_body')->nullable(); // e.g., "Dinas Tenaga Kerja", "Third Party Inspector"
            
            // Dates
            $table->date('issued_date')->nullable();
            $table->date('expiry_date')->nullable();
            
            // Status (computed from expiry_date)
            $table->enum('status', ['valid', 'expiring_soon', 'expiring_critical', 'expired'])->default('valid');
            
            // Notes
            $table->text('notes')->nullable();
            
            // Audit fields
            $table->foreignId('created_by')->constrained('users');
            $table->foreignId('updated_by')->constrained('users');
            $table->timestamps();
            $table->softDeletes();
            
            // Indexes
            $table->index(['asset_id', 'status']);
            $table->index('expiry_date');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('asset_certificates');
    }
};
