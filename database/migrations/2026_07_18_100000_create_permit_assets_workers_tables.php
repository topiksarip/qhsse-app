<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('permit_assets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('permit_id')->constrained('permits')->cascadeOnDelete();
            $table->foreignId('asset_id')->constrained('assets')->cascadeOnDelete();
            $table->string('role')->nullable()->comment('Peran alat dalam izin kerja (opsional)');
            $table->timestamps();

            $table->unique(['permit_id', 'asset_id']);
        });

        Schema::create('permit_workers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('permit_id')->constrained('permits')->cascadeOnDelete();
            $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
            $table->string('role')->nullable()->comment('Peran pekerja dalam izin kerja (mis. operator, pengawas)');
            $table->timestamps();

            $table->unique(['permit_id', 'employee_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('permit_workers');
        Schema::dropIfExists('permit_assets');
    }
};
