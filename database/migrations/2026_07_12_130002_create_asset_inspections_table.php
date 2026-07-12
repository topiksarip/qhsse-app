<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('asset_inspections', function (Blueprint $table) {
            $table->id();
            
            // Foreign key to asset
            $table->foreignId('asset_id')->constrained('assets')->cascadeOnDelete();
            
            // Inspection details
            $table->date('inspection_date');
            $table->foreignId('inspector_id')->constrained('users');
            
            // Result
            $table->enum('result', ['pass', 'fail', 'maintenance_required'])->default('pass');
            
            // Next inspection
            $table->date('next_inspection_date')->nullable();
            
            // Notes & findings
            $table->text('notes')->nullable();
            $table->text('findings')->nullable();
            
            // CAPA link (if result = fail)
            $table->foreignId('capa_action_id')->nullable()->constrained('capa_actions')->nullOnDelete();
            
            // Audit fields
            $table->foreignId('created_by')->constrained('users');
            $table->foreignId('updated_by')->constrained('users');
            $table->timestamps();
            $table->softDeletes();
            
            // Indexes
            $table->index(['asset_id', 'inspection_date']);
            $table->index('result');
            $table->index('next_inspection_date');
            $table->index('inspector_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('asset_inspections');
    }
};
