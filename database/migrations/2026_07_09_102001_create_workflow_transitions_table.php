<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('workflow_transitions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workflow_definition_id')->constrained('workflow_definitions')->cascadeOnDelete();
            $table->string('from_status', 100);
            $table->string('to_status', 100);
            $table->string('action_key', 100);
            $table->string('action_label');
            $table->boolean('requires_reason')->default(false);
            $table->string('required_permission')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['workflow_definition_id', 'from_status', 'action_key']);
            $table->index(['workflow_definition_id', 'from_status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('workflow_transitions');
    }
};
