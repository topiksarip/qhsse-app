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
        Schema::create('legal_obligations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('legal_register_id')->constrained('legal_register')->cascadeOnDelete();
            $table->text('obligation_description');
            $table->string('frequency', 20);
            $table->date('last_completed')->nullable();
            $table->date('next_due')->nullable();
            $table->foreignId('evidence_file_id')->nullable()->constrained('managed_files');
            $table->string('status', 20)->default('pending');
            $table->timestamps();

            // Indexes
            $table->index('legal_register_id');
            $table->index('status');
            $table->index('next_due');
            $table->index('frequency');
            $table->index('evidence_file_id');
        });

        // PostgreSQL check constraints
        if (DB::connection()->getDriverName() === 'pgsql') {
            DB::statement("
                ALTER TABLE legal_obligations ADD CONSTRAINT legal_obligations_frequency_check 
                CHECK (frequency IN ('monthly','quarterly','annual'))
            ");
            DB::statement("
                ALTER TABLE legal_obligations ADD CONSTRAINT legal_obligations_status_check 
                CHECK (status IN ('pending','completed'))
            ");
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('legal_obligations');
    }
};
