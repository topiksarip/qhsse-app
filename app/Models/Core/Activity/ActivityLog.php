<?php

namespace App\Models\Core\Activity;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ActivityLog extends Model
{
    protected $fillable = [
        'module_name', 'reference_id', 'event', 'description', 'actor_id',
        'actor_name', 'properties',
    ];

    protected function casts(): array
    {
        return [
            'reference_id' => 'integer',
            'properties' => 'array',
        ];
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_id');
    }
}
