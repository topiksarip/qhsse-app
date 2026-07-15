<?php

namespace App\Models\Modules\Asset;

use App\Models\Concerns\Auditable;
use App\Models\Contracts\ProvidesAuditContext;
use App\Models\Core\MasterData\Area;
use App\Models\Core\MasterData\Department;
use App\Models\Core\MasterData\Site;
use App\Models\User;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Asset extends Model implements ProvidesAuditContext
{
    use Auditable, HasFactory, SoftDeletes;

    protected $fillable = [
        'asset_number',
        'name',
        'category',
        'serial_number',
        'model',
        'manufacturer',
        'site_id',
        'area_id',
        'department_id',
        'purchase_date',
        'installation_date',
        'warranty_expiry_date',
        'status',
        'safety_critical',
        'next_inspection_date',
        'description',
        'notes',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'purchase_date' => 'date',
        'installation_date' => 'date',
        'warranty_expiry_date' => 'date',
        'next_inspection_date' => 'date',
        'safety_critical' => 'boolean',
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

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function certificates(): HasMany
    {
        return $this->hasMany(AssetCertificate::class);
    }

    public function inspections(): HasMany
    {
        return $this->hasMany(AssetInspection::class);
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

    public function scopeDecommissioned(Builder $query): Builder
    {
        return $query->where('status', 'decommissioned');
    }

    public function scopeSafetyCritical(Builder $query): Builder
    {
        return $query->where('safety_critical', true);
    }

    public function scopeByCategory(Builder $query, string $category): Builder
    {
        return $query->where('category', $category);
    }

    public function scopeBySite(Builder $query, int $siteId): Builder
    {
        return $query->where('site_id', $siteId);
    }

    public function scopeByArea(Builder $query, int $areaId): Builder
    {
        return $query->where('area_id', $areaId);
    }

    public function scopeByDepartment(Builder $query, int $departmentId): Builder
    {
        return $query->where('department_id', $departmentId);
    }

    public function scopeInspectionOverdue(Builder $query): Builder
    {
        return $query->whereNotNull('next_inspection_date')
            ->whereDate('next_inspection_date', '<', now());
    }

    public function scopeWarrantyExpired(Builder $query): Builder
    {
        return $query->whereNotNull('warranty_expiry_date')
            ->whereDate('warranty_expiry_date', '<', now());
    }

    public function scopeSearch(Builder $query, ?string $search): Builder
    {
        if (empty($search)) {
            return $query;
        }

        return $query->where(function (Builder $q) use ($search) {
            $q->where('asset_number', 'LIKE', "%{$search}%")
                ->orWhere('name', 'LIKE', "%{$search}%")
                ->orWhere('serial_number', 'LIKE', "%{$search}%")
                ->orWhere('model', 'LIKE', "%{$search}%")
                ->orWhere('manufacturer', 'LIKE', "%{$search}%");
        });
    }

    // Accessors
    public function getIsDecommissionedAttribute(): bool
    {
        return $this->status === 'decommissioned';
    }

    public function getIsActiveAttribute(): bool
    {
        return $this->status === 'active';
    }

    public function getHasExpiredCertificatesAttribute(): bool
    {
        return $this->certificates()
            ->activeRecords()
            ->where('status', 'expired')
            ->exists();
    }

    public function getHasExpiringCertificatesAttribute(): bool
    {
        return $this->certificates()
            ->activeRecords()
            ->whereIn('status', ['expiring_soon', 'expiring_critical'])
            ->exists();
    }

    public function getIsInspectionOverdueAttribute(): bool
    {
        if (! $this->next_inspection_date) {
            return false;
        }

        return $this->next_inspection_date->isPast();
    }

    public function getHasFailedInspectionAttribute(): bool
    {
        return $this->inspections()
            ->activeRecords()
            ->where('result', 'fail')
            ->whereDoesntHave('capaAction')
            ->exists();
    }

    // Static methods
    public static function getCategories(): array
    {
        return [
            'equipment' => 'Equipment',
            'machinery' => 'Machinery',
            'vehicle' => 'Vehicle',
            'safety_equipment' => 'Safety Equipment',
            'fire_equipment' => 'Fire Equipment',
            'lifting' => 'Lifting Equipment',
            'other' => 'Other',
        ];
    }

    public static function getStatuses(): array
    {
        return [
            'active' => 'Active',
            'inactive' => 'Inactive',
            'decommissioned' => 'Decommissioned',
        ];
    }

    // ProvidesAuditContext implementation
    public function auditContext(): array
    {
        return [
            'module_name' => 'asset',
            'reference_id' => $this->id,
        ];
    }
}
