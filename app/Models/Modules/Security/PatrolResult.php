<?php

namespace App\Models\Modules\Security;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PatrolResult extends Model
{
    use HasFactory;

    protected $fillable = [
        'patrol_checklist_id',
        'checkpoint',
        'result',
        'findings',
        'checked_at',
    ];

    protected $casts = [
        'checked_at' => 'datetime',
    ];

    // Relationships
    public function patrolChecklist(): BelongsTo
    {
        return $this->belongsTo(PatrolChecklist::class);
    }

    // Helpers
    public static function getResults(): array
    {
        return [
            'ok' => 'OK',
            'issue' => 'Issue Found',
            'na' => 'N/A',
        ];
    }

    public function getResultLabel(): string
    {
        return self::getResults()[$this->result] ?? $this->result;
    }

    public function hasIssue(): bool
    {
        return $this->result === 'issue';
    }
}
