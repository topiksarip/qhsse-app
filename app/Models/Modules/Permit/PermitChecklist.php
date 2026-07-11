<?php

namespace App\Models\Modules\Permit;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PermitChecklist extends Model
{
    /** @use HasFactory<\Database\Factories\Modules\Permit\PermitChecklistFactory> */
    use HasFactory;

    protected $fillable = [
        'permit_id',
        'item_text',
        'is_checked',
        'checked_by',
        'checked_at',
    ];

    protected function casts(): array
    {
        return [
            'is_checked' => 'boolean',
            'checked_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<Permit, PermitChecklist> */
    public function permit(): BelongsTo
    {
        return $this->belongsTo(Permit::class);
    }

    /** @return BelongsTo<User, PermitChecklist> */
    public function checker(): BelongsTo
    {
        return $this->belongsTo(User::class, 'checked_by');
    }
}
