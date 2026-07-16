<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('apd_catalogs', function (Blueprint $table) {
            $table->id();

            // Catalog code (PPE-YYYY-NNNN)
            $table->string('catalog_code', 50)->unique();

            // Classification
            $table->enum('category', [
                'head_protection',
                'eye_face_protection',
                'hearing_protection',
                'respiratory_protection',
                'hand_protection',
                'foot_protection',
                'body_protection',
                'fall_protection',
                'other',
            ])->default('other');
            $table->enum('track_type', ['serial', 'batch'])->default('serial');

            // Identity
            $table->string('name');
            $table->string('sku')->nullable();
            $table->string('manufacturer')->nullable();
            $table->string('model')->nullable();

            // Specification
            $table->text('description')->nullable();
            $table->string('standard')->nullable()->comment('SNI/EN/ANSI standard');
            $table->string('size')->nullable();
            $table->enum('protection_level', ['low', 'medium', 'high', 'critical'])->nullable();

            // Lifecycle parameters
            $table->integer('default_lifespan_months')->nullable()->comment('Usable life since manufacture');
            $table->integer('inspection_interval_days')->nullable()->comment('Scheduled inspection cadence');
            $table->decimal('default_unit_cost', 14, 2)->nullable();

            // Stock control
            $table->integer('min_stock')->default(0);
            $table->integer('reorder_point')->default(0);
            $table->boolean('is_active')->default(true);

            // Audit fields
            $table->foreignId('created_by')->constrained('users');
            $table->foreignId('updated_by')->constrained('users');
            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index(['category', 'is_active']);
            $table->index('track_type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('apd_catalogs');
    }
};
