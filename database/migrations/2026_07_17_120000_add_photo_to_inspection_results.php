<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('inspection_results', function (Blueprint $table) {
            $table->string('photo')->nullable()->after('remark')
                ->comment('Managed file reference path for item-type photo evidence');
        });
    }

    public function down(): void
    {
        Schema::table('inspection_results', function (Blueprint $table) {
            $table->dropColumn('photo');
        });
    }
};
