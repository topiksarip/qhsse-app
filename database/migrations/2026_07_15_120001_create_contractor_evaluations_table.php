<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('contractor_evaluations', function (Blueprint $table) {
            $table->id();

            $table->foreignId('contractor_id')
                ->constrained('contractors')
                ->cascadeOnDelete();

            $table->date('evaluation_date');
            $table->foreignId('evaluator_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            // Criteria scores: associative JSON { criterion_key: score }
            $table->json('criteria')->nullable();

            // Total score (sum of criteria) — 0..100 typical
            $table->integer('total_score')->default(0);

            // Derived result: pass | conditional | fail
            $table->string('result')->default('conditional');

            $table->text('notes')->nullable();

            // Audit
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index('contractor_id');
            $table->index('evaluation_date');
            $table->index('result');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contractor_evaluations');
    }
};
