<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('apd_issuances', function (Blueprint $table) {
            $table->id();

            // Issue number (immutable) PPE-ISSUE-YYYY-NNNN
            $table->string('issue_number', 50)->unique();

            // Item being issued (1 issuance = 1 item serial, or 1 batch lot qty)
            $table->foreignId('apd_item_id')->constrained('apd_items')->cascadeOnDelete();

            // Quantity: serial always 1, batch = qty issued
            $table->integer('quantity')->default(1);

            // Holder (polymorphic: employee / contractor / location)
            $table->string('holder_type');
            $table->unsignedBigInteger('holder_id');

            // Workflow actors
            $table->foreignId('requested_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('issued_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('returned_by')->nullable()->constrained('users')->nullOnDelete();

            // Dates
            $table->date('requested_date')->nullable();
            $table->date('issue_date')->nullable();
            $table->date('expected_return_date')->nullable();
            $table->date('returned_date')->nullable();
            $table->date('expiry_date')->nullable();

            // Workflow status
            $table->enum('status', ['draft', 'requested', 'approved', 'issued', 'returned', 'disposed', 'rejected'])
                ->default('draft');

            // Condition out (when issued) / in (when returned)
            $table->enum('condition_out', ['new', 'good', 'fair', 'poor'])->nullable();
            $table->enum('condition_in', ['new', 'good', 'fair', 'poor', 'damaged'])->nullable();

            $table->text('notes')->nullable();

            $table->foreignId('created_by')->constrained('users');
            $table->foreignId('updated_by')->constrained('users');
            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index(['holder_type', 'holder_id'], 'apd_issuances_holder_index');
            $table->index(['status']);
            $table->index(['apd_item_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('apd_issuances');
    }
};
