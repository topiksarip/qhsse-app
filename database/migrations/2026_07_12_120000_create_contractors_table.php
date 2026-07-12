<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('contractors', function (Blueprint $table) {
            $table->id();
            
            // Basic Info
            $table->string('contractor_number')->unique();
            $table->string('company_name');
            $table->string('business_registration_number')->nullable();
            $table->string('tax_id')->nullable();
            
            // Contact Info
            $table->string('contact_person');
            $table->string('contact_phone');
            $table->string('contact_email')->nullable();
            $table->text('address')->nullable();
            
            // Business Details
            $table->string('business_type')->nullable(); // construction, maintenance, consulting, etc
            $table->text('scope_of_work')->nullable();
            $table->text('specialization')->nullable();
            
            // Contract Details
            $table->date('contract_start_date')->nullable();
            $table->date('contract_end_date')->nullable();
            $table->string('contract_status')->default('pending'); // pending, active, suspended, expired, terminated
            $table->text('contract_terms')->nullable();
            
            // QHSSE Requirements
            $table->boolean('safety_induction_required')->default(true);
            $table->date('safety_induction_date')->nullable();
            $table->date('safety_induction_expiry')->nullable();
            $table->boolean('insurance_required')->default(true);
            $table->string('insurance_policy_number')->nullable();
            $table->date('insurance_expiry')->nullable();
            
            // Performance Rating
            $table->decimal('performance_rating', 3, 2)->nullable(); // 0.00 to 5.00
            $table->integer('incident_count')->default(0);
            $table->integer('violation_count')->default(0);
            $table->text('performance_notes')->nullable();
            
            // Site Access
            $table->json('authorized_sites')->nullable(); // array of site IDs
            $table->json('authorized_areas')->nullable(); // array of area IDs
            
            // Documents
            $table->json('document_files')->nullable(); // contract, insurance, licenses
            
            // Approval Workflow
            $table->string('approval_status')->default('draft'); // draft, submitted, approved, rejected
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->text('approval_notes')->nullable();
            
            // Audit
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
            
            // Indexes
            $table->index('contractor_number');
            $table->index('company_name');
            $table->index('contract_status');
            $table->index('approval_status');
            $table->index(['contract_start_date', 'contract_end_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contractors');
    }
};
