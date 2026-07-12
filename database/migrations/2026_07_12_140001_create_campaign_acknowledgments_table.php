<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('campaign_acknowledgments', function (Blueprint $table) {
            $table->id();
            
            // Foreign keys
            $table->foreignId('campaign_id')->constrained('campaigns')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            
            // Acknowledgment details
            $table->timestamp('acknowledged_at');
            $table->string('ip_address', 45)->nullable(); // IPv4 or IPv6
            
            // Audit fields
            $table->timestamps();
            
            // Unique constraint: one user can acknowledge once per campaign
            $table->unique(['campaign_id', 'user_id']);
            
            // Indexes
            $table->index(['campaign_id', 'acknowledged_at']);
            $table->index('user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('campaign_acknowledgments');
    }
};
