<?php

namespace App\Models\Modules\Security;

use App\Models\Core\MasterData\Area;
use App\Models\Core\MasterData\Severity;
use App\Models\Core\MasterData\Site;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SecurityIncident extends Model
{
    use HasFactory;

    protected $fillable = [
        'security_number',
        'type',
        'title',
        'description',
        'site_id',
        'area_id',
        'occurred_at',
        'reported_by',
        'severity_id',
        'status',
        'resolution',
        'resolved_at',
    ];

    protected $casts = [
        'occurred_at' => 'datetime',
        'resolved_at' => 'datetime',
    ];

    // Relationships
    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class);
    }

    public function area(): BelongsTo
    {
        return $this->belongsTo(Area::class);
    }

    public function reporter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reported_by');
    }

    public function severity(): BelongsTo
    {
        return $this->belongsTo(Severity::class);
    }

    // Scopes
    public function scopeForSite($query, int $siteId)
    {
        return $query->where('site_id', $siteId);
    }

    public function scopeByType($query, string $type)
    {
        return $query->where('type', $type);
    }

    public function scopeByStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    public function scopeOpen($query)
    {
        return $query->whereIn('status', ['reported', 'under_investigation']);
    }

    public function scopeClosed($query)
    {
        return $query->where('status', 'closed');
    }

    // Helpers
    public function isOpen(): bool
    {
        return in_array($this->status, ['reported', 'under_investigation']);
    }

    public function isClosed(): bool
    {
        return $this->status === 'closed';
    }

    public function canBeEdited(): bool
    {
        return $this->status !== 'closed';
    }

    public static function getTypes(): array
    {
        return [
            'unauthorized_access' => 'Unauthorized Access',
            'theft' => 'Theft',
            'vandalism' => 'Vandalism',
            'trespass' => 'Trespass',
            'suspicious_activity' => 'Suspicious Activity',
            'other' => 'Other',
        ];
    }

    public static function getStatuses(): array
    {
        return [
            'reported' => 'Reported',
            'under_investigation' => 'Under Investigation',
            'closed' => 'Closed',
        ];
    }

    public function getTypeLabel(): string
    {
        return self::getTypes()[$this->type] ?? $this->type;
    }

    public function getStatusLabel(): string
    {
        return self::getStatuses()[$this->status] ?? $this->status;
    }
}
