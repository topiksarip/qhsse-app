<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('audits', function (Blueprint $table) {
            $table->id();
            $table->string('audit_number', 50)->unique();
            $table->string('title', 255);
            $table->string('audit_type', 20);
            $table->text('scope')->nullable();
            $table->foreignId('department_id')->nullable()->constrained('departments')->nullOnDelete();
            $table->foreignId('lead_auditor_id')->constrained('users')->restrictOnDelete();
            $table->date('scheduled_date');
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->date('report_date')->nullable();
            $table->date('close_date')->nullable();
            $table->string('status', 20)->default('planned');
            $table->text('summary')->nullable();
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->timestamps();

            $table->index(['status', 'audit_type']);
            $table->index('department_id');
            $table->index('lead_auditor_id');
            $table->index('created_by');
        });

        Schema::create('audit_findings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('audit_id')->constrained('audits')->cascadeOnDelete();
            $table->string('finding_number', 60);
            $table->string('classification', 20);
            $table->text('description');
            $table->text('recommendation')->nullable();
            $table->foreignId('capa_action_id')->nullable()->constrained('capa_actions')->nullOnDelete();
            $table->string('status', 20)->default('open');
            $table->date('due_date')->nullable();
            $table->date('closed_date')->nullable();
            $table->foreignId('closed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index('audit_id');
            $table->index('capa_action_id');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_findings');
        Schema::dropIfExists('audits');
    }
};
