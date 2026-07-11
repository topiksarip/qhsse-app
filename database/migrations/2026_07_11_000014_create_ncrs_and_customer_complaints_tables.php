<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ncrs', function (Blueprint $table) {
            $table->id();
            $table->string('ncr_number', 50)->unique();
            $table->string('title');
            $table->string('source', 50);
            $table->text('description');
            $table->foreignId('site_id')->constrained()->restrictOnDelete();
            $table->foreignId('department_id')->nullable()->constrained()->restrictOnDelete();
            $table->string('product_service')->nullable();
            $table->string('batch_lot', 100)->nullable();
            $table->string('customer_name')->nullable();
            $table->foreignId('severity_id')->constrained()->restrictOnDelete();
            $table->string('status', 50)->default('open');
            $table->text('root_cause')->nullable();
            $table->text('corrective_action')->nullable();
            $table->text('preventive_action')->nullable();
            $table->foreignId('capa_action_id')->nullable()->constrained()->restrictOnDelete();
            $table->timestamp('closed_at')->nullable();
            $table->timestamps();

            $table->index(['site_id', 'status']);
            $table->index('department_id');
            $table->index('source');
            $table->index('severity_id');
            $table->index('created_at');
        });

        Schema::create('customer_complaints', function (Blueprint $table) {
            $table->id();
            $table->string('complaint_number', 50)->unique();
            $table->string('customer_name');
            $table->string('customer_contact')->nullable();
            $table->string('title');
            $table->text('description');
            $table->foreignId('site_id')->constrained()->restrictOnDelete();
            $table->string('product_service')->nullable();
            $table->foreignId('severity_id')->constrained()->restrictOnDelete();
            $table->string('status', 50)->default('open');
            $table->text('resolution')->nullable();
            $table->foreignId('ncr_id')->nullable()->constrained('ncrs')->restrictOnDelete();
            $table->timestamp('closed_at')->nullable();
            $table->timestamps();

            $table->index(['site_id', 'status']);
            $table->index('customer_name');
            $table->index('severity_id');
            $table->index('ncr_id');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customer_complaints');
        Schema::dropIfExists('ncrs');
    }
};
