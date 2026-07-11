<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const DOCUMENT_TYPES = ['sop', 'wi', 'jsa', 'hiradc', 'msds', 'policy', 'form', 'manual', 'other'];

    private const DOCUMENT_STATUSES = ['draft', 'review', 'approved', 'effective', 'obsolete', 'rejected'];

    private const REVIEW_DECISIONS = ['pending', 'approve', 'reject', 'revise'];

    public function up(): void
    {
        if (DB::getDriverName() === 'pgsql') {
            $this->upgradePostgres();
        } else {
            $this->upgradePortable();
        }

        $this->addMissingIndexes();
    }

    public function down(): void
    {
        $this->dropAddedIndexes();

        if (DB::getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE controlled_documents DROP CONSTRAINT IF EXISTS controlled_documents_type_check');
            DB::statement('ALTER TABLE controlled_documents DROP CONSTRAINT IF EXISTS controlled_documents_status_check');
            DB::statement('ALTER TABLE document_reviews DROP CONSTRAINT IF EXISTS document_reviews_decision_check');
        }

        DB::table('controlled_documents')->whereNull('title')->update(['title' => 'Untitled document']);
        DB::table('controlled_documents')->whereNull('type')->update(['type' => 'other']);
        DB::table('controlled_documents')->whereNull('version')->update(['version' => '1.0']);

        Schema::table('controlled_documents', function (Blueprint $table): void {
            $table->string('title', 255)->nullable(false)->change();
            $table->string('type', 20)->nullable(false)->change();
            $table->string('version', 20)->default('1.0')->nullable(false)->change();
            $table->string('status', 20)->default('draft')->change();
        });

        Schema::table('document_reviews', function (Blueprint $table): void {
            $table->string('decision', 20)->default('pending')->change();
        });
    }

    private function upgradePostgres(): void
    {
        DB::statement('ALTER TABLE controlled_documents ALTER COLUMN title DROP NOT NULL');
        DB::statement('ALTER TABLE controlled_documents ALTER COLUMN type DROP NOT NULL');
        DB::statement('ALTER TABLE controlled_documents ALTER COLUMN version DROP NOT NULL');
        DB::statement('ALTER TABLE controlled_documents ALTER COLUMN version DROP DEFAULT');

        DB::statement('ALTER TABLE controlled_documents DROP CONSTRAINT IF EXISTS controlled_documents_type_check');
        DB::statement('ALTER TABLE controlled_documents DROP CONSTRAINT IF EXISTS controlled_documents_status_check');
        DB::statement('ALTER TABLE document_reviews DROP CONSTRAINT IF EXISTS document_reviews_decision_check');
        DB::statement("ALTER TABLE controlled_documents ADD CONSTRAINT controlled_documents_type_check CHECK (type IS NULL OR type IN ('sop', 'wi', 'jsa', 'hiradc', 'msds', 'policy', 'form', 'manual', 'other'))");
        DB::statement("ALTER TABLE controlled_documents ADD CONSTRAINT controlled_documents_status_check CHECK (status IN ('draft', 'review', 'approved', 'effective', 'obsolete', 'rejected'))");
        DB::statement("ALTER TABLE document_reviews ADD CONSTRAINT document_reviews_decision_check CHECK (decision IN ('pending', 'approve', 'reject', 'revise'))");
    }

    private function upgradePortable(): void
    {
        Schema::table('controlled_documents', function (Blueprint $table): void {
            $table->string('title', 255)->nullable()->change();
            $table->enum('type', self::DOCUMENT_TYPES)->nullable()->change();
            $table->string('version', 20)->nullable()->default(null)->change();
            $table->enum('status', self::DOCUMENT_STATUSES)->default('draft')->change();
        });

        Schema::table('document_reviews', function (Blueprint $table): void {
            $table->enum('decision', self::REVIEW_DECISIONS)->default('pending')->change();
        });
    }

    private function addMissingIndexes(): void
    {
        foreach (['approver_id', 'effective_date', 'review_date', 'expiry_date', 'created_at'] as $column) {
            if (! Schema::hasIndex('controlled_documents', [$column])) {
                Schema::table('controlled_documents', fn (Blueprint $table) => $table->index($column));
            }
        }
    }

    private function dropAddedIndexes(): void
    {
        foreach (['approver_id', 'effective_date', 'review_date', 'expiry_date', 'created_at'] as $column) {
            if (Schema::hasIndex('controlled_documents', [$column])) {
                Schema::table('controlled_documents', fn (Blueprint $table) => $table->dropIndex([$column]));
            }
        }
    }
};
