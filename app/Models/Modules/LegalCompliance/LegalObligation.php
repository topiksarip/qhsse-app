<?php

declare(strict_types=1);

namespace App\Models\Modules\LegalCompliance;

use App\Models\Core\ManagedFile;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LegalObligation extends Model
{
    use HasFactory;

    protected $table = 'legal_obligations';

    protected $fillable = [
        'legal_register_id',
        'obligation_description',
        'frequency',
        'last_completed',
        'next_due',
        'evidence_file_id',
        'status',
    ];

    protected $casts = [
        'last_completed' => 'date',
        'next_due' => 'date',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    protected $with = [];

    // Relationships

    public function legalRegister(): BelongsTo
    {
        return $this->belongsTo(LegalRegister::class, 'legal_register_id');
    }

    public function evidenceFile(): BelongsTo
    {
        return $this->belongsTo(ManagedFile::class, 'evidence_file_id');
    }

    // Scopes

    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', 'pending');
    }

    public function scopeCompleted(Builder $query): Builder
    {
        return $query->where('status', 'completed');
    }

    public function scopeOverdue(Builder $query): Builder
    {
        return $query->where('status', 'pending')
            ->whereNotNull('next_due')
            ->where('next_due', '<', now()->toDateString());
    }

    public function scopeDueSoon(Builder $query, int $days = 7): Builder
    {
        return $query->where('status', 'pending')
            ->whereNotNull('next_due')
            ->where('next_due', '<=', now()->addDays($days)->toDateString())
            ->where('next_due', '>=', now()->toDateString());
    }

    public function scopeByFrequency(Builder $query, string $frequency): Builder
    {
        return $query->where('frequency', $frequency);
    }

    // Helpers

    public function isOverdue(): bool
    {
        return $this->status === 'pending'
            && $this->next_due !== null
            && $this->next_due < now()->toDateString();
    }

    public function isDueSoon(int $days = 7): bool
    {
        return $this->status === 'pending'
            && $this->next_due !== null
            && $this->next_due <= now()->addDays($days)->toDateString()
            && $this->next_due >= now()->toDateString();
    }

    public function getDaysOverdue(): ?int
    {
        if (! $this->isOverdue()) {
            return null;
        }

        return now()->diffInDays(Carbon::parse($this->next_due), false);
    }

    public function getDaysUntilDue(): ?int
    {
        if ($this->next_due === null || $this->status !== 'pending') {
            return null;
        }

        return now()->diffInDays(Carbon::parse($this->next_due), false);
    }

    public function calculateNextDue(?string $lastCompleted = null): ?string
    {
        $baseDate = $lastCompleted ? Carbon::parse($lastCompleted) : ($this->last_completed ? Carbon::parse($this->last_completed) : null);

        if (! $baseDate) {
            return null;
        }

        return match ($this->frequency) {
            'monthly' => $baseDate->addMonth()->toDateString(),
            'quarterly' => $baseDate->addMonths(3)->toDateString(),
            'annual' => $baseDate->addYear()->toDateString(),
            default => null,
        };
    }

    public function getFrequencyLabelAttribute(): string
    {
        return match ($this->frequency) {
            'monthly' => 'Bulanan',
            'quarterly' => 'Triwulanan',
            'annual' => 'Tahunan',
            default => ucfirst($this->frequency),
        };
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'pending' => 'Menunggu',
            'completed' => 'Selesai',
            default => ucfirst($this->status),
        };
    }

    public function getStatusColorAttribute(): string
    {
        if ($this->isOverdue()) {
            return 'red';
        }

        if ($this->isDueSoon()) {
            return 'orange';
        }

        return match ($this->status) {
            'pending' => 'yellow',
            'completed' => 'green',
            default => 'gray',
        };
    }
}
