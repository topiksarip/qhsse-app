<?php

declare(strict_types=1);

namespace App\Models\Modules\EmergencyPreparedness;

use App\Models\Core\MasterData\Site;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmergencyContact extends Model
{
    use HasFactory;

    protected $table = 'emergency_contacts';

    protected $fillable = [
        'name',
        'role',
        'phone',
        'email',
        'site_id',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    protected $with = [];

    // Relationships

    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class);
    }

    // Scopes

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeInactive(Builder $query): Builder
    {
        return $query->where('is_active', false);
    }

    public function scopeBySite(Builder $query, int $siteId): Builder
    {
        return $query->where('site_id', $siteId);
    }

    public function scopeByRole(Builder $query, string $role): Builder
    {
        return $query->where('role', 'ilike', "%{$role}%");
    }

    // Helpers

    public function isActive(): bool
    {
        return $this->is_active === true;
    }

    public function getFormattedPhoneAttribute(): string
    {
        // Simple formatting - can be enhanced based on phone format standards
        return $this->phone;
    }
}
