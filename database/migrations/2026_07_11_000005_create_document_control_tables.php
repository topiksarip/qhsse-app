<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('controlled_documents', function (Blueprint $table) {
            $table->id();
            $table->string('document_number', 50)->unique();
            $table->string('title', 255)->nullable();
            $table->enum('type', ['sop', 'wi', 'jsa', 'hiradc', 'msds', 'policy', 'form', 'manual', 'other'])->nullable();
            $table->string('version', 20)->nullable();
            $table->text('revision_notes')->nullable();
            $table->date('effective_date')->nullable();
            $table->date('review_date')->nullable();
            $table->date('expiry_date')->nullable();
            $table->foreignId('department_id')->nullable()->constrained('departments')->nullOnDelete();
            $table->foreignId('owner_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('approver_id')->nullable()->constrained('users')->nullOnDelete();
            $table->enum('status', ['draft', 'review', 'approved', 'effective', 'obsolete', 'rejected'])->default('draft');
            $table->boolean('is_confidential')->default(false);
            $table->timestamps();

            $table->index(['status', 'type']);
            $table->index('department_id');
            $table->index('owner_id');
            $table->index('approver_id');
            $table->index('effective_date');
            $table->index('review_date');
            $table->index('expiry_date');
            $table->index('created_at');
        });

        Schema::create('document_reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('document_id')->constrained('controlled_documents')->cascadeOnDelete();
            $table->foreignId('reviewer_id')->nullable()->constrained('users')->nullOnDelete();
            $table->date('review_date')->nullable();
            $table->text('review_notes')->nullable();
            $table->enum('decision', ['pending', 'approve', 'reject', 'revise'])->default('pending');
            $table->timestamps();

            $table->index('document_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('document_reviews');
        Schema::dropIfExists('controlled_documents');
    }
};
