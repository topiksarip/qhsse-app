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
            $table->string('title', 255);
            $table->string('type', 20);
            $table->string('version', 20)->default('1.0');
            $table->text('revision_notes')->nullable();
            $table->date('effective_date')->nullable();
            $table->date('review_date')->nullable();
            $table->date('expiry_date')->nullable();
            $table->foreignId('department_id')->nullable()->constrained('departments')->nullOnDelete();
            $table->foreignId('owner_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('approver_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('status', 20)->default('draft');
            $table->boolean('is_confidential')->default(false);
            $table->timestamps();

            $table->index(['status', 'type']);
            $table->index('department_id');
            $table->index('owner_id');
        });

        Schema::create('document_reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('document_id')->constrained('controlled_documents')->cascadeOnDelete();
            $table->foreignId('reviewer_id')->nullable()->constrained('users')->nullOnDelete();
            $table->date('review_date')->nullable();
            $table->text('review_notes')->nullable();
            $table->string('decision', 20)->default('pending');
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
