<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('risk_registers', function (Blueprint $table) {
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete()->after('status');
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete()->after('created_by');
        });

        Schema::table('training_records', function (Blueprint $table) {
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete()->after('updated_at');
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete()->after('created_by');
        });
    }

    public function down(): void
    {
        Schema::table('training_records', function (Blueprint $table) {
            $table->dropConstrainedForeignId('updated_by');
            $table->dropConstrainedForeignId('created_by');
        });

        Schema::table('risk_registers', function (Blueprint $table) {
            $table->dropConstrainedForeignId('updated_by');
            $table->dropConstrainedForeignId('created_by');
        });
    }
};
