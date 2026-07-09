<?php

namespace App\Models\Core\Files;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ManagedFile extends Model
{
    /** @use HasFactory<\Database\Factories\Core\Files\ManagedFileFactory> */
    use HasFactory;

    protected $fillable = [
        'module_name',
        'reference_id',
        'collection',
        'disk',
        'path',
        'original_name',
        'stored_name',
        'mime_type',
        'extension',
        'size',
        'checksum',
        'metadata',
        'uploaded_by',
        'deleted_at',
        'deleted_by',
    ];

    protected function casts(): array
    {
        return [
            'metadata' => 'array',
            'reference_id' => 'integer',
            'size' => 'integer',
            'deleted_at' => 'datetime',
        ];
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function deleter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'deleted_by');
    }

    public function scopeActive($query)
    {
        return $query->whereNull('deleted_at');
    }

    public function getIsDeletedAttribute(): bool
    {
        return $this->deleted_at !== null;
    }
}
