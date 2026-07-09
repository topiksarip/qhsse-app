<?php

namespace App\Core\Files;

use App\Models\Core\Files\ManagedFile;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ManagedFileService
{
    public const DISK = 'local';

    public function store(UploadedFile $file, FileReference $reference, User $uploader, array $metadata = []): ManagedFile
    {
        $extension = strtolower($file->getClientOriginalExtension() ?: $file->extension() ?: 'bin');
        $storedName = Str::uuid()->toString().'.'.$extension;
        $directory = implode('/', [
            'managed-files',
            $reference->moduleName,
            (string) $reference->referenceId,
            $reference->collection,
        ]);

        $path = $file->storeAs($directory, $storedName, self::DISK);
        $contents = Storage::disk(self::DISK)->get($path);

        return ManagedFile::create([
            'module_name' => $reference->moduleName,
            'reference_id' => $reference->referenceId,
            'collection' => $reference->collection,
            'disk' => self::DISK,
            'path' => $path,
            'original_name' => $file->getClientOriginalName(),
            'stored_name' => $storedName,
            'mime_type' => $file->getClientMimeType() ?: $file->getMimeType() ?: 'application/octet-stream',
            'extension' => $extension,
            'size' => $file->getSize() ?: strlen($contents),
            'checksum' => hash('sha256', $contents),
            'metadata' => $metadata,
            'uploaded_by' => $uploader->id,
        ]);
    }

    public function markDeleted(ManagedFile $file, User $actor): void
    {
        $file->update([
            'deleted_at' => now(),
            'deleted_by' => $actor->id,
        ]);
    }
}
