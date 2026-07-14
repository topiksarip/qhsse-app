<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $certificates = DB::table('asset_certificates')
            ->orderBy('id')
            ->get(['id', 'asset_id'])
            ->keyBy('id');
        $files = DB::table('managed_files')
            ->where('module_name', 'asset')
            ->where('collection', 'certificate')
            ->whereNull('deleted_at')
            ->orderBy('id')
            ->get(['id', 'reference_id', 'metadata']);
        $assignments = [];
        $assignedCertificateIds = [];
        $assignedFileIds = [];

        foreach ($files as $file) {
            $metadata = is_string($file->metadata) ? json_decode($file->metadata, true) : (array) $file->metadata;
            $certificateId = isset($metadata['certificate_id']) ? (int) $metadata['certificate_id'] : null;

            if ($certificateId === null) {
                continue;
            }

            $certificate = $certificates->get($certificateId);
            if ($certificate === null || (int) $certificate->asset_id !== (int) $file->reference_id) {
                throw new RuntimeException("Managed file {$file->id} has invalid certificate metadata.");
            }
            if (isset($assignedCertificateIds[$certificateId])) {
                throw new RuntimeException("Certificate {$certificateId} has multiple active legacy evidence files.");
            }

            $assignments[$certificateId] = (int) $file->id;
            $assignedCertificateIds[$certificateId] = true;
            $assignedFileIds[(int) $file->id] = true;
        }

        $assetIds = $certificates->pluck('asset_id')->merge($files->pluck('reference_id'))->unique();
        foreach ($assetIds as $assetId) {
            $unassignedCertificates = $certificates
                ->where('asset_id', $assetId)
                ->reject(fn (object $certificate) => isset($assignedCertificateIds[$certificate->id]));
            $unassignedFiles = $files
                ->where('reference_id', $assetId)
                ->reject(fn (object $file) => isset($assignedFileIds[$file->id]));

            if ($unassignedFiles->isEmpty()) {
                continue;
            }
            if ($unassignedCertificates->count() !== 1 || $unassignedFiles->count() !== 1) {
                throw new RuntimeException("Legacy certificate evidence for asset {$assetId} is ambiguous.");
            }

            $certificateId = (int) $unassignedCertificates->first()->id;
            $fileId = (int) $unassignedFiles->first()->id;
            $assignments[$certificateId] = $fileId;
        }

        Schema::table('asset_certificates', function (Blueprint $table): void {
            $table->foreignId('certificate_file_id')
                ->nullable()
                ->after('issuing_body')
                ->constrained('managed_files')
                ->nullOnDelete();
        });

        foreach ($assignments as $certificateId => $fileId) {
            DB::table('asset_certificates')
                ->where('id', $certificateId)
                ->update(['certificate_file_id' => $fileId]);
        }
    }

    public function down(): void
    {
        DB::table('asset_certificates')
            ->whereNotNull('certificate_file_id')
            ->orderBy('id')
            ->get(['id', 'certificate_file_id'])
            ->each(function (object $certificate): void {
                $file = DB::table('managed_files')->where('id', $certificate->certificate_file_id)->first(['metadata']);
                $metadata = is_string($file?->metadata) ? json_decode($file->metadata, true) : (array) ($file?->metadata ?? []);
                $metadata['certificate_id'] = (int) $certificate->id;

                DB::table('managed_files')
                    ->where('id', $certificate->certificate_file_id)
                    ->update(['metadata' => json_encode($metadata, JSON_THROW_ON_ERROR)]);
            });

        Schema::table('asset_certificates', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('certificate_file_id');
        });
    }
};
