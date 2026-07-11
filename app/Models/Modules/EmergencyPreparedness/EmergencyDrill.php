<?php

declare(strict_types=1);

namespace App\Models\Modules\EmergencyPreparedness;

use App\Models\Core\MasterData\Site;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmergencyDrill extends Model
{
    use HasFactory;

    protected $table = 'emergency_drills';

    protected $fillable = [
        'drill_number',
        'emergency_plan_id',
        'scheduled_date',
        'executed_date',
        'site_id',
        'participants_count',
        'observer_id',
        'result',
        'findings',
        'recommendations',
        'status',
    ];

    protected $casts = [
        'scheduled_date' => 'date',
        'executed_date' => 'date',
        'participants_count' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    protected $with = [];

    // Relationships

    public function emergencyPlan(): BelongsTo
    {
        return $this->belongsTo(EmergencyPlan::class, 'emergency_plan_id');
    }

    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class);
    }

    public function observer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'observer_id');
    }

    // Scopes

    public function scopeScheduled(Builder $query): Builder
    {
        return $query->where('status', 'scheduled');
    }

    public function scopeExecuted(Builder $query): Builder
    {
        return $query->where('status', 'executed');
    }

    public function scopeUpcoming(Builder $query): Builder
    {
        return $query->where('status', 'scheduled')
            ->where('scheduled_date', '>=', now()->toDateString());
    }

    public function scopeOverdue(Builder $query): Builder
    {
        return $query->where('status', 'scheduled')
            ->where('scheduled_date', '<', now()->toDateString());
    }

    public function scopeByResult(Builder $query, string $result): Builder
    {
        return $query->where('result', $result);
    }

    public function scopePassed(Builder $query): Builder
    {
        return $query->where('result', 'pass');
    }

    public function scopeFailed(Builder $query): Builder
    {
        return $query->where('result', 'fail');
    }

    public function scopeNeedsImprovement(Builder $query): Builder
    {
        return $query->where('result', 'needs_improvement');
    }

    public function scopeBySite(Builder $query, int $siteId): Builder
    {
        return $query->where('site_id', $siteId);
    }

    public function scopeByPlan(Builder $query, int $planId): Builder
    {
        return $query->where('emergency_plan_id', $planId);
    }

    // Helpers

    public function isScheduled(): bool
    {
        return $this->status === 'scheduled';
    }

    public function isExecuted(): bool
    {
        return $this->status === 'executed';
    }

    public function isUpcoming(): bool
    {
        return $this->status === 'scheduled' 
            && $this->scheduled_date >= now()->toDateString();
    }

    public function isOverdue(): bool
    {
        return $this->status === 'scheduled' 
            && $this->scheduled_date < now()->toDateString();
    }

    public function isPassed(): bool
    {
        return $this->result === 'pass';
    }

    public function isFailed(): bool
    {
        return $this->result === 'fail';
    }

    public function needsImprovement(): bool
    {
        return $this->result === 'needs_improvement';
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'scheduled' => 'Terjadwal',
            'executed' => 'Dilaksanakan',
            default => ucfirst($this->status),
        };
    }

    public function getResultLabelAttribute(): ?string
    {
        if (! $this->result) {
            return null;
        }

        return match ($this->result) {
            'pass' => 'Lulus',
            'fail' => 'Gagal',
            'needs_improvement' => 'Perlu Perbaikan',
            default => ucfirst($this->result),
        };
    }

    public function getResultColorAttribute(): ?string
    {
        if (! $this->result) {
            return null;
        }

        return match ($this->result) {
            'pass' => 'green',
            'fail' => 'red',
            'needs_improvement' => 'orange',
            default => 'gray',
        };
    }

    public function getDaysUntilScheduledAttribute(): ?int
    {
        if ($this->status !== 'scheduled') {
            return null;
        }

        return now()->diffInDays($this->scheduled_date, false);
    }
}
