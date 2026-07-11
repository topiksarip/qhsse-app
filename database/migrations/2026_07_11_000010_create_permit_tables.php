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
        Schema::create('permits', function (Blueprint $table) {
            $table->id();
            $table->string('permit_number', 50)->unique();
            $table->string('type', 50);
            $table->string('title');
            $table->text('description');
            $table->foreignId('site_id')->constrained()->restrictOnDelete();
            $table->foreignId('area_id')->nullable()->constrained()->restrictOnDelete();
            $table->foreignId('department_id')->nullable()->constrained()->restrictOnDelete();
            $table->foreignId('contractor_id')->nullable()->constrained('companies')->restrictOnDelete();
            $table->string('work_location');
            $table->text('work_description');
            $table->timestamp('start_datetime');
            $table->timestamp('end_datetime');
            $table->integer('validity_hours');
            $table->string('status', 50)->default('draft');
            $table->string('risk_level', 50)->nullable();
            $table->string('jsa_reference')->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->foreignId('closed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('closed_at')->nullable();
            $table->text('cancellation_reason')->nullable();
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->timestamps();

            // Indexes
            $table->index('site_id');
            $table->index('area_id');
            $table->index('department_id');
            $table->index('contractor_id');
            $table->index('type');
            $table->index('status');
            $table->index('risk_level');
            $table->index('start_datetime');
            $table->index('end_datetime');
            $table->index(['start_datetime', 'end_datetime']);
            $table->index('approved_by');
            $table->index('closed_by');
            $table->index('created_by');
        });

        Schema::create('permit_checklists', function (Blueprint $table) {
            $table->id();
            $table->foreignId('permit_id')->constrained()->cascadeOnDelete();
            $table->text('item_text');
            $table->boolean('is_checked')->default(false);
            $table->foreignId('checked_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('checked_at')->nullable();
            $table->timestamps();

            // Indexes
            $table->index('permit_id');
            $table->index('is_checked');
            $table->index('checked_by');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('permit_checklists');
        Schema::dropIfExists('permits');
    }
};
