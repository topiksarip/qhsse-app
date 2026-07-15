<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('contractors', function (Blueprint $table) {
            // Prequalification
            $table->boolean('is_prequalified')->default(false)->after('approval_notes');
            $table->date('prequalified_until')->nullable()->after('is_prequalified');

            // Safety rating (derived from latest evaluations)
            $table->string('safety_rating')->nullable()->after('prequalified_until');

            $table->index('is_prequalified');
            $table->index('prequalified_until');
            $table->index('safety_rating');
        });
    }

    public function down(): void
    {
        Schema::table('contractors', function (Blueprint $table) {
            $table->dropIndex(['is_prequalified']);
            $table->dropIndex(['prequalified_until']);
            $table->dropIndex(['safety_rating']);
            $table->dropColumn(['is_prequalified', 'prequalified_until', 'safety_rating']);
        });
    }
};
