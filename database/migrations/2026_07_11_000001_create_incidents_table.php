<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('incidents', function (Blueprint $table) {
            $table->id();
            $table->string('incident_number')->unique();
            $table->string('title');
            $table->string('category');
            $table->timestamp('occurred_at');
            $table->foreignId('site_id')->constrained()->cascadeOnDelete();
            $table->foreignId('area_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('department_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('reporter_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('severity_id')->constrained()->cascadeOnDelete();
            $table->foreignId('priority_id')->constrained()->cascadeOnDelete();
            $table->text('description');
            $table->text('immediate_action')->nullable();
            $table->string('status')->default('draft');
            $table->timestamps();

            $table->index('status');
            $table->index('category');
            $table->index('occurred_at');
        });

        Schema::create('incident_involved_persons', function (Blueprint $table) {
            $table->id();
            $table->foreignId('incident_id')->constrained()->cascadeOnDelete();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->string('note')->nullable();
            $table->timestamps();

            $table->unique(['incident_id', 'employee_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('incident_involved_persons');
        Schema::dropIfExists('incidents');
    }
};
