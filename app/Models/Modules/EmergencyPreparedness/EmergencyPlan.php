<?php

declare(strict_types=1);

namespace App\Models\Modules\EmergencyPreparedness;

use App\Models\Core\MasterData\Site;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\AsArrayObject;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EmergencyPlan extends Model
{
    use HasFactory;

    protected $table = 'emergency_plans';

    protected $fillable = [
        'plan_number',
        'name',
        'type',
        'site_id',
        'description',
        'response_procedure',
        'escalation_procedure',
        'contact_person_id',
        'emergency_contacts',
        'equipment_needed',
    ];

    protected $casts = [
        'emergency_contacts' => AsArrayObject::class,
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    protected $with = [];

    // Relationships

    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class);
    }

    public function contactPerson(): BelongsTo
    {
        return $this->belongsTo(User::class, 'contact_person_id');
    }

    public function drills(): HasMany
    {
        return $this->hasMany(EmergencyDrill::class, 'emergency_plan_id');
    }

    // Scopes

    public function scopeByType(Builder $query, string $type): Builder
    {
        return $query->where('type', $type);
    }

    public function scopeBySite(Builder $query, int $siteId): Builder
    {
        return $query->where('site_id', $siteId);
    }

    public function scopeByContactPerson(Builder $query, int $userId): Builder
    {
        return $query->where('contact_person_id', $userId);
    }

    public function scopeFire(Builder $query): Builder
    {
        return $query->where('type', 'fire');
    }

    public function scopeMedical(Builder $query): Builder
    {
        return $query->where('type', 'medical');
    }

    public function scopeSpill(Builder $query): Builder
    {
        return $query->where('type', 'spill');
    }

    public function scopeEvacuation(Builder $query): Builder
    {
        return $query->where('type', 'evacuation');
    }

    public function scopeNaturalDisaster(Builder $query): Builder
    {
        return $query->where('type', 'natural_disaster');
    }

    public function scopeSecurity(Builder $query): Builder
    {
        return $query->where('type', 'security');
    }

    // Helpers

    public function getTypeLabelAttribute(): string
    {
        return match ($this->type) {
            'fire' => 'Kebakaran',
            'medical' => 'Medis',
            'spill' => 'Tumpahan',
            'evacuation' => 'Evakuasi',
            'natural_disaster' => 'Bencana Alam',
            'security' => 'Keamanan',
            'other' => 'Lainnya',
            default => ucfirst($this->type),
        };
    }

    public function hasScheduledDrills(): bool
    {
        return $this->drills()->where('status', 'scheduled')->exists();
    }

    public function hasExecutedDrills(): bool
    {
        return $this->drills()->where('status', 'executed')->exists();
    }

    public function getLastDrillAttribute(): ?EmergencyDrill
    {
        return $this->drills()
            ->where('status', 'executed')
            ->orderBy('executed_date', 'desc')
            ->first();
    }

    public function getUpcomingDrillsCountAttribute(): int
    {
        return $this->drills()
            ->where('status', 'scheduled')
            ->where('scheduled_date', '>=', now()->toDateString())
            ->count();
    }

    public function getOverdueDrillsCountAttribute(): int
    {
        return $this->drills()
            ->where('status', 'scheduled')
            ->where('scheduled_date', '<', now()->toDateString())
            ->count();
    }
}
