<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('campaigns', function (Blueprint $table) {
            $table->id();
            
            // Unique campaign number (COM-YYYY-NNNN)
            $table->string('campaign_number', 50)->unique();
            
            // Basic Information
            $table->string('title');
            $table->enum('type', [
                'safety_alert',
                'lesson_learned',
                'campaign',
                'announcement',
                'newsletter'
            ]);
            $table->text('content'); // Rich text content
            
            // Target Audience
            $table->enum('target_audience', [
                'all',
                'specific_site',
                'specific_department',
                'specific_role'
            ])->default('all');
            $table->foreignId('site_id')->nullable()->constrained('sites')->nullOnDelete();
            $table->foreignId('department_id')->nullable()->constrained('departments')->nullOnDelete();
            $table->string('target_role')->nullable(); // Role name string
            
            // Status & Publishing
            $table->enum('status', ['draft', 'published'])->default('draft');
            $table->timestamp('published_at')->nullable();
            $table->date('expires_at')->nullable();
            
            // Metrics
            $table->unsignedInteger('view_count')->default(0);
            
            // Author
            $table->foreignId('author_id')->constrained('users');
            
            // Audit fields
            $table->foreignId('created_by')->constrained('users');
            $table->foreignId('updated_by')->constrained('users');
            $table->timestamps();
            $table->softDeletes();
            
            // Indexes
            $table->index(['type', 'status']);
            $table->index(['target_audience', 'status']);
            $table->index(['status', 'published_at']);
            $table->index('expires_at');
            $table->index('author_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('campaigns');
    }
};
