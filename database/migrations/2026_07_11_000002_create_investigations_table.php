<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('investigations', function (Blueprint $table) {
            $table->id();
            $table->string('investigation_number')->unique();
            $table->foreignId('incident_id')->constrained('incidents')->cascadeOnDelete();
            $table->string('title');
            $table->string('status')->default('draft');
            $table->text('root_cause')->nullable();
            $table->json('five_whys')->nullable();
            $table->json('fishbone')->nullable();
            $table->json('contributing_factors')->nullable();
            $table->json('timeline_events')->nullable();
            $table->text('recommendations')->nullable();
            $table->foreignId('investigator_id')->constrained('users')->cascadeOnDelete();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->index('status');
            $table->index('incident_id');
        });

        Schema::create('investigation_team', function (Blueprint $table) {
            $table->id();
            $table->foreignId('investigation_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('role')->nullable();
            $table->timestamps();

            $table->unique(['investigation_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('investigation_team');
        Schema::dropIfExists('investigations');
    }
};
