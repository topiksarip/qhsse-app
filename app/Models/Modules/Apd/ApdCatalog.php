<?php

namespace App\Models\Modules\Apd;

use App\Models\Concerns\Auditable;
use App\Models\Contracts\ProvidesAuditContext;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class ApdCatalog extends Model implements ProvidesAuditContext
{
    use Auditable, HasFactory, SoftDeletes;

    protected $table = 'apd_catalogs';

    protected $fillable = [
        'catalog_code',
        'category',
        'track_type',
        'name',
        'sku',
        'manufacturer',
        'model',
        'description',
        'standard',
        'size',
        'protection_level',
        'default_lifespan_months',
        'inspection_interval_days',
        'default_unit_cost',
        'min_stock',
        'reorder_point',
        'is_active',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'default_lifespan_months' => 'integer',
        'inspection_interval_days' => 'integer',
        'default_unit_cost' => 'decimal:2',
        'min_stock' => 'integer',
        'reorder_point' => 'integer',
        'is_active' => 'boolean',
    ];

    // Relationships
    public function items(): HasMany
    {
        return $this->hasMany(ApdItem::class, 'catalog_id');
    }

    public function apdRequirements(): HasMany
    {
        return $this->hasMany(ApdRequirement::class, 'apd_catalog_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    // Scopes
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeSerial(Builder $query): Builder
    {
        return $query->where('track_type', 'serial');
    }

    public function scopeBatch(Builder $query): Builder
    {
        return $query->where('track_type', 'batch');
    }

    public function scopeByCategory(Builder $query, string $category): Builder
    {
        return $query->where('category', $category);
    }

    public function scopeLowStock(Builder $query): Builder
    {
        return $query->where('min_stock', '>', 0)
            ->whereRaw('(SELECT COALESCE(SUM(quantity),0) FROM apd_items WHERE apd_items.catalog_id = apd_catalogs.id AND apd_items.status IN (\'in_stock\',\'issued\')) < apd_catalogs.min_stock');
    }

    // Accessors
    public function getLowStockAttribute(): bool
    {
        return $this->min_stock > 0
            && $this->active_quantity < $this->min_stock;
    }

    public function getActiveQuantityAttribute(): int
    {
        return $this->items()
            ->whereIn('status', ['in_stock', 'issued'])
            ->sum('quantity');
    }

    // Static methods
    public static function getCategories(): array
    {
        return [
            'head_protection' => 'Pelindung Kepala',
            'eye_face_protection' => 'Pelindung Mata & Wajah',
            'hearing_protection' => 'Pelindung Pendengaran',
            'respiratory_protection' => 'Pelindung Pernapasan',
            'hand_protection' => 'Sarung Tangan',
            'foot_protection' => 'Pelindung Kaki',
            'body_protection' => 'Pelindung Tubuh',
            'fall_protection' => 'Pelindung Jatuh',
            'other' => 'Lainnya',
        ];
    }

    public static function getTrackTypes(): array
    {
        return [
            'serial' => 'Per-Serial (track per unit)',
            'batch' => 'Per-Batch (kuantitas)',
        ];
    }

    public static function getProtectionLevels(): array
    {
        return [
            'low' => 'Rendah',
            'medium' => 'Sedang',
            'high' => 'Tinggi',
            'critical' => 'Kritis',
        ];
    }

    public static function getStatuses(): array
    {
        return [
            'active' => 'Aktif',
            'inactive' => 'Nonaktif',
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
