<?php

namespace App\Models\Modules\Communication;

use App\Models\Contracts\ProvidesAuditContext;
use App\Models\Core\MasterData\Site;
use App\Models\Core\MasterData\Department;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Campaign extends Model implements ProvidesAuditContext
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'campaign_number',
        'title',
        'type',
        'content',
        'target_audience',
        'site_id',
        'department_id',
        'target_role',
        'status',
        'published_at',
        'expires_at',
        'view_count',
        'author_id',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'published_at' => 'datetime',
        'expires_at' => 'date',
        'view_count' => 'integer',
    ];

    // Relationships
    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class);
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function acknowledgments(): HasMany
    {
        return $this->hasMany(CampaignAcknowledgment::class);
    }

    // Scopes
    public function scopePublished($query)
    {
        return $query->where('status', 'published');
    }

    public function scopeDraft($query)
    {
        return $query->where('status', 'draft');
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'published')
            ->where(function ($q) {
                $q->whereNull('expires_at')
                  ->orWhere('expires_at', '>', now());
            });
    }

    public function scopeExpired($query)
    {
        return $query->where('status', 'published')
            ->whereNotNull('expires_at')
            ->where('expires_at', '<=', now());
    }

    public function scopeOfType($query, string $type)
    {
        return $query->where('type', $type);
    }

    public function scopeSafetyAlert($query)
    {
        return $query->where('type', 'safety_alert');
    }

    public function scopeForSite($query, int $siteId)
    {
        return $query->where(function ($q) use ($siteId) {
            $q->where('target_audience', 'all')
              ->orWhere(function ($sq) use ($siteId) {
                  $sq->where('target_audience', 'specific_site')
                     ->where('site_id', $siteId);
              });
        });
    }

    public function scopeForDepartment($query, int $departmentId)
    {
        return $query->where(function ($q) use ($departmentId) {
            $q->where('target_audience', 'all')
              ->orWhere(function ($sq) use ($departmentId) {
                  $sq->where('target_audience', 'specific_department')
                     ->where('department_id', $departmentId);
              });
        });
    }

    public function scopeForRole($query, string $role)
    {
        return $query->where(function ($q) use ($role) {
            $q->where('target_audience', 'all')
              ->orWhere(function ($sq) use ($role) {
                  $sq->where('target_audience', 'specific_role')
                     ->where('target_role', $role);
              });
        });
    }

    // Accessors
    public function getTypeLabelAttribute(): string
    {
        return match ($this->type) {
            'safety_alert' => 'Safety Alert',
            'lesson_learned' => 'Lesson Learned',
            'campaign' => 'Kampanye',
            'announcement' => 'Pengumuman',
            'newsletter' => 'Newsletter',
            default => $this->type,
        };
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'draft' => 'Draft',
            'published' => 'Published',
            default => $this->status,
        };
    }

    public function getTargetAudienceLabelAttribute(): string
    {
        return match ($this->target_audience) {
            'all' => 'Semua Karyawan',
            'specific_site' => 'Site: ' . ($this->site->name ?? '-'),
            'specific_department' => 'Departemen: ' . ($this->department->name ?? '-'),
            'specific_role' => 'Role: ' . $this->target_role,
            default => $this->target_audience,
        };
    }

    public function getIsExpiredAttribute(): bool
    {
        return $this->status === 'published'
            && $this->expires_at !== null
            && $this->expires_at->isPast();
    }

    public function getIsActiveAttribute(): bool
    {
        return $this->status === 'published'
            && ($this->expires_at === null || $this->expires_at->isFuture());
    }

    public function getRequiresAcknowledgmentAttribute(): bool
    {
        return $this->type === 'safety_alert';
    }

    public function getAcknowledgmentCountAttribute(): int
    {
        return $this->acknowledgments()->count();
    }

    // Helper methods
    public function isAcknowledgedBy(User $user): bool
    {
        return $this->acknowledgments()
            ->where('user_id', $user->id)
            ->exists();
    }

    public function canBeEditedBy(User $user): bool
    {
        // Only draft campaigns can be edited
        if ($this->status !== 'draft') {
            return false;
        }

        return $user->can('communication.campaigns.update');
    }

    public function canBePublished(): bool
    {
        return $this->status === 'draft';
    }

    // ProvidesAuditContext implementation
    public function auditContext(): array
    {
        return [
            'entity' => 'campaign',
            'action' => 'view',
            'entity_type' => self::class,
            'entity_id' => $this->id,
            'campaign_number' => $this->campaign_number,
            'title' => $this->title,
            'type' => $this->type,
            'status' => $this->status,
        ];
    }
}
