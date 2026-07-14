<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('02_incident_reporting', function (Blueprint $table) {
            $table->id();
            $table->string('number')->unique();
            $table->string('title');
            $table->text('description')->nullable();
            $table->foreignId('site_id')->nullable()->constrained('sites');
            $table->foreignId('area_id')->nullable()->constrained('areas');
            $table->foreignId('department_id')->nullable()->constrained('departments');
            $table->foreignId('company_id')->nullable()->constrained('companies');
            $table->foreignId('category_id')->nullable()->constrained('categories');
            $table->foreignId('severity_id')->nullable()->constrained('severities');
            $table->foreignId('priority_id')->nullable()->constrained('priorities');
            $table->foreignId('reporter_id')->nullable()->constrained('users');
            $table->foreignId('assigned_to')->nullable()->constrained('users');
            $table->foreignId('reviewer_id')->nullable()->constrained('users');
            $table->foreignId('approver_id')->nullable()->constrained('users');
            $table->foreignId('verifier_id')->nullable()->constrained('users');
            $table->date('event_date')->nullable();
            $table->date('due_date')->nullable();
            $table->string('status')->default('draft');
            $table->json('meta')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users');
            $table->foreignId('updated_by')->nullable()->constrained('users');
            $table->timestamps();
            $table->softDeletes();
            $table->index(['status']);
            $table->index(['site_id']);
            $table->index(['department_id']);
            $table->index(['due_date']);
            $table->index(['assigned_to']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('02_incident_reporting');
    }
};
