<?php

namespace App\Models\Core\Notifications;

use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Model;

class NotificationTemplate extends Model
{
    use Auditable;

    protected $fillable = ['type', 'title_template', 'message_template', 'channels', 'is_active'];

    protected function casts(): array
    {
        return [
            'channels' => 'array',
            'is_active' => 'boolean',
        ];
    }
}
