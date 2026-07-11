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
        Schema::create('training_programs', function (Blueprint $table) {
            $table->id();
            $table->string('code', 50)->unique();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('category', 50);
            $table->integer('duration_hours');
            $table->boolean('is_certification')->default(false);
            $table->integer('validity_months')->nullable();
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
        });

        Schema::create('training_records', function (Blueprint $table) {
            $table->id();
            $table->string('training_number', 50)->unique();
            $table->foreignId('employee_id')->constrained()->restrictOnDelete();
            $table->foreignId('training_program_id')->constrained()->restrictOnDelete();
            $table->string('provider')->nullable();
            $table->date('start_date');
            $table->date('end_date')->nullable();
            $table->string('status', 50)->default('scheduled')->index();
            $table->decimal('score', 5, 2)->nullable();
            $table->string('result', 20)->nullable();
            $table->string('certificate_number')->nullable();
            $table->foreignId('certificate_file_id')->nullable()->constrained('managed_files')->nullOnDelete();
            $table->date('expiry_date')->nullable()->index();
            $table->text('notes')->nullable();
            $table->timestamps();

            // Indexes
            $table->index('employee_id');
            $table->index('training_program_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('training_records');
        Schema::dropIfExists('training_programs');
    }
};
