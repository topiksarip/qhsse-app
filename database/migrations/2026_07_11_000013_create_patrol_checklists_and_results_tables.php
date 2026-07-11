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
        Schema::create('patrol_checklists', function (Blueprint $table) {
            $table->id();
            $table->string('patrol_number', 50)->unique();
            $table->string('title');
            $table->text('description')->nullable();
            $table->foreignId('site_id')->constrained()->restrictOnDelete();
            $table->foreignId('area_id')->nullable()->constrained()->restrictOnDelete();
            $table->timestamp('scheduled_at');
            $table->foreignId('assigned_to')->constrained('users')->restrictOnDelete();
            $table->string('status', 50)->default('scheduled');
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->foreignId('completed_by')->nullable()->constrained('users')->restrictOnDelete();
            $table->text('notes')->nullable();
            $table->timestamps();

            // Indexes
            $table->index('site_id');
            $table->index('area_id');
            $table->index('status');
            $table->index('scheduled_at');
            $table->index('assigned_to');
            $table->index('created_at');
        });

        Schema::create('patrol_results', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patrol_checklist_id')->constrained()->cascadeOnDelete();
            $table->string('checkpoint');
            $table->string('result', 20);
            $table->text('findings')->nullable();
            $table->timestamp('checked_at')->nullable();
            $table->timestamps();

            // Indexes
            $table->index('patrol_checklist_id');
            $table->index('result');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('patrol_results');
        Schema::dropIfExists('patrol_checklists');
    }
};
