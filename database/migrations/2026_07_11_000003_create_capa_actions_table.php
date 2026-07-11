<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('capa_actions', function (Blueprint $table) {
            $table->id();
            $table->string('action_number')->unique();
            $table->string('title');
            $table->text('description');
            $table->string('source_module')->nullable();
            $table->bigInteger('source_reference_id')->nullable();
            $table->string('source_type')->nullable();
            $table->foreignId('site_id')->constrained()->cascadeOnDelete();
            $table->foreignId('department_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('assigned_to')->constrained('users')->cascadeOnDelete();
            $table->foreignId('assigned_by')->constrained('users')->cascadeOnDelete();
            $table->timestamp('assigned_at')->nullable();
            $table->date('due_date')->nullable();
            $table->foreignId('severity_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('priority_id')->constrained()->cascadeOnDelete();
            $table->string('status')->default('open');
            $table->text('verification_note')->nullable();
            $table->foreignId('verified_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('verified_at')->nullable();
            $table->timestamp('closed_at')->nullable();
            $table->timestamps();

            $table->index('status');
            $table->index('source_module');
            $table->index('assigned_to');
            $table->index('due_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('capa_actions');
    }
};
