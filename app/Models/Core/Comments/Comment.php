<?php

namespace App\Models\Core\Comments;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Comment extends Model
{
    protected $fillable = [
        'module_name', 'reference_id', 'parent_id', 'author_id', 'body',
        'mentions', 'is_internal', 'edited_at', 'deleted_at', 'deleted_by',
    ];

    protected function casts(): array
    {
        return [
            'reference_id' => 'integer',
            'mentions' => 'array',
            'is_internal' => 'boolean',
            'edited_at' => 'datetime',
            'deleted_at' => 'datetime',
        ];
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function replies(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id');
    }

    public function deleter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'deleted_by');
    }

    public function scopeActive($query)
    {
        return $query->whereNull('deleted_at');
    }
}
