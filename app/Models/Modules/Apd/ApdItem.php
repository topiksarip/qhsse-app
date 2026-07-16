<?php

namespace App\Models\Modules\Apd;

use App\Models\Concerns\Auditable;
use App\Models\Contracts\ProvidesAuditContext;
use App\Models\Core\Files\ManagedFile;
use App\Models\Core\MasterData\Area;
use App\Models\Core\MasterData\Department;
use App\Models\Core\MasterData\Site;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class ApdItem extends Model implements ProvidesAuditContext
{
    use Auditable, HasFactory, SoftDeletes;

    protected $table = 'apd_items';

    protected $fillable = [
        'item_number',
        'catalog_id',
        'track_type',
        'serial_number',
        'quantity',
        'unit_cost',
        'site_id',
        'area_id',
        'department_id',
        'storage_location',
        'status',
        'condition',
        'manufacture_date',
        'purchase_date',
        'received_date',
        'expiry_date',
        'next_inspection_date',
        'holder_type',
        'holder_id',
        'notes',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'unit_cost' => 'decimal:2',
        'manufacture_date' => 'date',
        'purchase_date' => 'date',
        'received_date' => 'date',
        'expiry_date' => 'date',
        'next_inspection_date' => 'date',
    ];

    // Relationships
    public function catalog(): BelongsTo
    {
        return $this->belongsTo(ApdCatalog::class, 'catalog_id');
    }

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

    public function holder(): BelongsTo
    {
        return $this->morphTo();
    }

    public function files(): HasMany
    {
        return $this->hasMany(ManagedFile::class, 'reference_id')
            ->where('module_name', 'apd');
    }

    public function trainingRecords(): HasMany
    {
        return $this->hasMany(\App\Models\Modules\Training\TrainingRecord::class, 'apd_item_id');
    }

    // Scopes
    public function scopeInStock(Builder $query): Builder
    {
        return $query->where('status', 'in_stock');
    }

    public function scopeIssued(Builder $query): Builder
    {
        return $query->where('status', 'issued');
    }

    public function scopeByStatus(Builder $query, string $status): Builder
    {
        return $query->where('status', $status);
    }

    public function scopeByCatalog(Builder $query, int $catalogId): Builder
    {
        return $query->where('catalog_id', $catalogId);
    }

    public function scopeBySite(Builder $query, int $siteId): Builder
    {
        return $query->where('site_id', $siteId);
    }

    public function scopeExpired(Builder $query): Builder
    {
        return $query->whereNotNull('expiry_date')
            ->whereDate('expiry_date', '<', now());
    }

    public function scopeExpiringSoon(Builder $query, int $days = 30): Builder
    {
        return $query->whereNotNull('expiry_date')
            ->whereDate('expiry_date', '>=', now())
            ->whereDate('expiry_date', '<=', now()->addDays($days));
    }

    public function scopeInspectionOverdue(Builder $query): Builder
    {
        return $query->whereNotNull('next_inspection_date')
            ->whereDate('next_inspection_date', '<', now());
    }

    public function scopeAvailable(Builder $query): Builder
    {
        return $query->whereIn('status', ['in_stock', 'issued']);
    }

    // Accessors
    public function getIsExpiredAttribute(): bool
    {
        return $this->expiry_date !== null && $this->expiry_date->isPast();
    }

    public function getIsInspectionOverdueAttribute(): bool
    {
        return $this->next_inspection_date !== null && $this->next_inspection_date->isPast();
    }

    public function getIsAvailableAttribute(): bool
    {
        return in_array($this->status, ['in_stock', 'issued'], true);
    }

    // Static methods
    public static function getStatuses(): array
    {
        return [
            'in_stock' => 'Di Gudang',
            'issued' => 'Terissued',
            'in_inspection' => 'Dalam Inspeksi',
            'damaged' => 'Rusak',
            'disposed' => 'Dimusnahkan',
            'lost' => 'Hilang',
        ];
    }

    public static function getConditions(): array
    {
        return [
            'new' => 'Baru',
            'good' => 'Baik',
            'fair' => 'Cukup',
            'poor' => 'Buruk',
        ];
    }

    // ProvidesAuditContext implementation
    public function auditContext(): array
    {
        return [
            'module_name' => 'apd',
            'reference_id' => $this->id,
        ];
    }
}
