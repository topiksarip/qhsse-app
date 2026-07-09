<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('workflow_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workflow_instance_id')->constrained('workflow_instances')->cascadeOnDelete();
            $table->string('module_name', 100);
            $table->unsignedBigInteger('reference_id');
            $table->string('from_status', 100)->nullable();
            $table->string('to_status', 100);
            $table->string('action_key', 100);
            $table->string('action_label');
            $table->text('reason')->nullable();
            $table->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['module_name', 'reference_id']);
            $table->index(['actor_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('workflow_histories');
    }
};
