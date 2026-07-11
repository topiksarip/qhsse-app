<?php

declare(strict_types=1);

namespace App\Models\Modules\LegalCompliance;

use App\Models\Core\MasterData\Department;
use App\Models\Core\MasterData\Site;
use App\Models\Modules\DocumentControl\Document;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LegalRegister extends Model
{
    use HasFactory;

    protected $table = 'legal_register';

    protected $fillable = [
        'register_number',
        'title',
        'regulation_name',
        'regulation_number',
        'issuing_body',
        'category',
        'compliance_status',
        'site_id',
        'department_id',
        'owner_id',
        'next_review_date',
        'document_id',
        'notes',
        'status',
    ];

    protected $casts = [
        'next_review_date' => 'date',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    protected $with = [];

    // Relationships

    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class);
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function document(): BelongsTo
    {
        return $this->belongsTo(Document::class);
    }

    public function obligations(): HasMany
    {
        return $this->hasMany(LegalObligation::class, 'legal_register_id');
    }

    // Scopes

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', 'active');
    }

    public function scopeInactive(Builder $query): Builder
    {
        return $query->where('status', 'inactive');
    }

    public function scopeByComplianceStatus(Builder $query, string $status): Builder
    {
        return $query->where('compliance_status', $status);
    }

    public function scopeCompliant(Builder $query): Builder
    {
        return $query->where('compliance_status', 'compliant');
    }

    public function scopeNonCompliant(Builder $query): Builder
    {
        return $query->where('compliance_status', 'non_compliant');
    }

    public function scopeInProgress(Builder $query): Builder
    {
        return $query->where('compliance_status', 'in_progress');
    }

    public function scopeByCategory(Builder $query, string $category): Builder
    {
        return $query->where('category', $category);
    }

    public function scopeBySite(Builder $query, int $siteId): Builder
    {
        return $query->where('site_id', $siteId);
    }

    public function scopeByDepartment(Builder $query, int $departmentId): Builder
    {
        return $query->where('department_id', $departmentId);
    }

    public function scopeByOwner(Builder $query, int $ownerId): Builder
    {
        return $query->where('owner_id', $ownerId);
    }

    public function scopeReviewDueSoon(Builder $query, int $days = 30): Builder
    {
        return $query->whereNotNull('next_review_date')
            ->where('next_review_date', '<=', now()->addDays($days)->toDateString())
            ->where('next_review_date', '>=', now()->toDateString());
    }

    public function scopeReviewOverdue(Builder $query): Builder
    {
        return $query->whereNotNull('next_review_date')
            ->where('next_review_date', '<', now()->toDateString());
    }

    // Accessors & Helpers

    public function isCompliant(): bool
    {
        return $this->compliance_status === 'compliant';
    }

    public function isNonCompliant(): bool
    {
        return $this->compliance_status === 'non_compliant';
    }

    public function isInProgress(): bool
    {
        return $this->compliance_status === 'in_progress';
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function hasOverdueObligations(): bool
    {
        return $this->obligations()->overdue()->exists();
    }

    public function hasDueSoonObligations(int $days = 7): bool
    {
        return $this->obligations()->dueSoon($days)->exists();
    }

    public function getComplianceStatusColorAttribute(): string
    {
        return match ($this->compliance_status) {
            'compliant' => 'green',
            'non_compliant' => 'red',
            'in_progress' => 'yellow',
            'not_applicable' => 'gray',
            default => 'gray',
        };
    }

    public function getCategoryLabelAttribute(): string
    {
        return match ($this->category) {
            'national' => 'Regulasi Nasional',
            'regional' => 'Regulasi Regional',
            'industry' => 'Standar Industri',
            'internal' => 'Regulasi Internal',
            default => ucfirst($this->category),
        };
    }

    public function getComplianceStatusLabelAttribute(): string
    {
        return match ($this->compliance_status) {
            'compliant' => 'Patuh',
            'non_compliant' => 'Tidak Patuh',
            'in_progress' => 'Dalam Proses',
            'not_applicable' => 'Tidak Berlaku',
            default => ucfirst($this->compliance_status),
        };
    }
}
