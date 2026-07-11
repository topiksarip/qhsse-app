<?php

namespace App\Models\Core\Notifications;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CoreNotification extends Model
{
    protected $fillable = [
        'recipient_id', 'actor_id', 'type', 'title', 'message', 'module_name',
        'reference_id', 'action_url', 'data', 'read_at', 'idempotency_key',
    ];

    protected function casts(): array
    {
        return [
            'reference_id' => 'integer',
            'data' => 'array',
            'read_at' => 'datetime',
        ];
    }

    public function recipient(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recipient_id');
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_id');
    }

    public function scopeUnread($query)
    {
        return $query->whereNull('read_at');
    }
}
