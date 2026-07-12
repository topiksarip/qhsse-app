<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('saved_reports', function (Blueprint $table) {
            $table->id();
            
            // Report identification
            $table->string('name');
            $table->foreignId('template_id')->constrained('report_templates');
            
            // Generation status
            $table->string('status')->default('pending'); // pending, processing, completed, failed
            
            // Parameters (stored as JSON)
            $table->json('parameters'); // date_from, date_to, site_id, department_id, format, include_charts
            
            // Output file
            $table->string('format'); // csv, pdf, excel
            $table->string('file_path')->nullable();
            $table->unsignedBigInteger('file_size')->nullable(); // bytes
            
            // Generation tracking
            $table->foreignId('generated_by')->constrained('users');
            $table->timestamp('generated_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('failed_at')->nullable();
            
            // Error tracking
            $table->text('error_message')->nullable();
            
            // Audit fields
            $table->foreignId('created_by')->constrained('users');
            $table->foreignId('updated_by')->constrained('users');
            $table->timestamps();
            $table->softDeletes();
            
            // Indexes
            $table->index('template_id');
            $table->index('status');
            $table->index('format');
            $table->index('generated_by');
            $table->index(['status', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('saved_reports');
    }
};
