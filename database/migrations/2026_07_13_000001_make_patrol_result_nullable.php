<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('patrol_results', function (Blueprint $table) {
            $table->string('result', 20)->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('patrol_results', function (Blueprint $table) {
            $table->string('result', 20)->nullable(false)->change();
        });
    }
};
