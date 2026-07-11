<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('legal_register', function (Blueprint $table) {
            $table->id();
            $table->string('register_number', 50)->unique();
            $table->string('title', 255);
            $table->string('regulation_name', 255);
            $table->string('regulation_number', 255);
            $table->string('issuing_body', 255);
            $table->string('category', 30);
            $table->string('compliance_status', 30)->default('in_progress');
            $table->foreignId('site_id')->nullable()->constrained('sites');
            $table->foreignId('department_id')->nullable()->constrained('departments');
            $table->foreignId('owner_id')->constrained('users');
            $table->date('next_review_date')->nullable();
            $table->bigInteger('document_id')->unsigned()->nullable();
            $table->text('notes')->nullable();
            $table->string('status', 20)->default('active');
            $table->timestamps();

            // Indexes
            $table->index('site_id');
            $table->index('department_id');
            $table->index('owner_id');
            $table->index('compliance_status');
            $table->index('category');
            $table->index('status');
            $table->index('next_review_date');
            $table->index('created_at');
        });

        // PostgreSQL check constraints
        if (DB::connection()->getDriverName() === 'pgsql') {
            DB::statement("
                ALTER TABLE legal_register ADD CONSTRAINT legal_register_category_check 
                CHECK (category IN ('national','regional','industry','internal'))
            ");
            DB::statement("
                ALTER TABLE legal_register ADD CONSTRAINT legal_register_compliance_status_check 
                CHECK (compliance_status IN ('compliant','non_compliant','in_progress','not_applicable'))
            ");
            DB::statement("
                ALTER TABLE legal_register ADD CONSTRAINT legal_register_status_check 
                CHECK (status IN ('active','inactive'))
            ");
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('legal_register');
    }
};
