<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('apd_items', function (Blueprint $table) {
            $table->id();

            // Item number (immutable)
            $table->string('item_number', 50)->unique();

            // Catalog link
            $table->foreignId('catalog_id')->constrained('apd_catalogs')->cascadeOnDelete();

            // Tracking
            $table->enum('track_type', ['serial', 'batch'])->default('serial');
            $table->string('serial_number')->nullable()->comment('filled for serial items');
            $table->integer('quantity')->default(1)->comment('1 for serial, qty for batch');
            $table->decimal('unit_cost', 14, 2)->nullable();

            // Storage location
            $table->foreignId('site_id')->constrained('sites')->cascadeOnDelete();
            $table->foreignId('area_id')->nullable()->constrained('areas')->nullOnDelete();
            $table->foreignId('department_id')->nullable()->constrained('departments')->nullOnDelete();
            $table->string('storage_location')->nullable();

            // Condition & status
            $table->enum('status', ['in_stock', 'issued', 'in_inspection', 'damaged', 'disposed', 'lost'])->default('in_stock');
            $table->enum('condition', ['new', 'good', 'fair', 'poor'])->default('new');

            // Lifecycle dates
            $table->date('manufacture_date')->nullable();
            $table->date('purchase_date')->nullable();
            $table->date('received_date')->nullable();
            $table->date('expiry_date')->nullable()->comment('shelf-life / expiry (e.g. respirator filter)');
            $table->date('next_inspection_date')->nullable();

            // Current holder (polymorphic: employee / contractor / location)
            $table->string('holder_type')->nullable();
            $table->unsignedBigInteger('holder_id')->nullable();

            // Notes
            $table->text('notes')->nullable();

            // Audit fields
            $table->foreignId('created_by')->constrained('users');
            $table->foreignId('updated_by')->constrained('users');
            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index(['catalog_id', 'status']);
            $table->index(['site_id', 'status']);
            $table->index(['status', 'expiry_date']);
            $table->index(['holder_type', 'holder_id']);
            $table->index('next_inspection_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('apd_items');
    }
};
