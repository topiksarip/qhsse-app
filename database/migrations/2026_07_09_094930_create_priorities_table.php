<?php
use Illuminate\Database\Migrations\Migration; use Illuminate\Database\Schema\Blueprint; use Illuminate\Support\Facades\Schema;
return new class extends Migration { public function up(): void { Schema::create('priorities', function (Blueprint $table) { $table->id(); $table->string('code')->unique(); $table->string('name'); $table->unsignedSmallInteger('sla_days')->default(7); $table->string('color')->default('gray'); $table->boolean('is_active')->default(true)->index(); $table->timestamps(); }); } public function down(): void { Schema::dropIfExists('priorities'); } };
