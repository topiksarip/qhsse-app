<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inspection_templates', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('category');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->index(['category', 'is_active']);
        });

        Schema::create('inspection_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('inspection_template_id')->constrained()->cascadeOnDelete();
            $table->text('question');
            $table->string('type'); // yes_no, safe_unsafe, na, scale, text
            $table->string('category')->nullable();
            $table->boolean('is_required')->default(true);
            $table->integer('order')->default(0);
            $table->timestamps();
            $table->index('inspection_template_id');
            $table->index('order');
        });

        Schema::create('inspections', function (Blueprint $table) {
            $table->id();
            $table->string('inspection_number')->unique();
            $table->foreignId('inspection_template_id')->constrained();
            $table->foreignId('site_id')->constrained();
            $table->foreignId('area_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('inspector_id')->constrained('users');
            $table->date('scheduled_at');
            $table->timestamp('executed_at')->nullable();
            $table->string('status')->default('pending');
            $table->string('overall_result')->default('pending');
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->index('status');
            $table->index('inspection_template_id');
            $table->index('inspector_id');
        });

        Schema::create('inspection_results', function (Blueprint $table) {
            $table->id();
            $table->foreignId('inspection_id')->constrained()->cascadeOnDelete();
            $table->foreignId('inspection_item_id')->constrained();
            $table->string('answer')->nullable();
            $table->text('remark')->nullable();
            $table->boolean('is_unsafe')->default(false);
            $table->timestamps();
            $table->unique(['inspection_id', 'inspection_item_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inspection_results');
        Schema::dropIfExists('inspections');
        Schema::dropIfExists('inspection_items');
        Schema::dropIfExists('inspection_templates');
    }
};
