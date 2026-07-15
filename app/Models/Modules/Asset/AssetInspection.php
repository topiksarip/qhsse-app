<?php

namespace App\Models\Modules\Asset;

use App\Models\Concerns\Auditable;
use App\Models\Contracts\ProvidesAuditContext;
use App\Models\Modules\Capa\CapaAction;
use App\Models\User;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class AssetInspection extends Model implements ProvidesAuditContext
{
    use Auditable, HasFactory, SoftDeletes;

    protected $fillable = [
        'asset_id',
        'inspection_date',
        'inspector_id',
        'result',
        'next_inspection_date',
        'notes',
        'findings',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'inspection_date' => 'date',
        'next_inspection_date' => 'date',
    ];

    // Relationships
    public function asset(): BelongsTo
    {
        return $this->belongsTo(Asset::class);
    }

    public function inspector(): BelongsTo
    {
        return $this->belongsTo(User::class, 'inspector_id');
    }

    public function capaAction(): HasOne
    {
        return $this->hasOne(CapaAction::class, 'source_reference_id')
            ->where('source_module', 'asset_inspection');
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
    public function scopeActiveRecords(Builder $query): Builder
    {
        return $query->whereNull('legacy_deleted_at');
    }

    public function scopePass(Builder $query): Builder
    {
        return $query->where('result', 'pass');
    }

    public function scopeFail(Builder $query): Builder
    {
        return $query->where('result', 'fail');
    }

    public function scopeMaintenanceRequired(Builder $query): Builder
    {
        return $query->where('result', 'maintenance_required');
    }

    public function scopeByAsset(Builder $query, int $assetId): Builder
    {
        return $query->where('asset_id', $assetId);
    }

    public function scopeByInspector(Builder $query, int $inspectorId): Builder
    {
        return $query->where('inspector_id', $inspectorId);
    }

    public function scopeWithoutCapa(Builder $query): Builder
    {
        return $query->whereDoesntHave('capaAction');
    }

    // Accessors
    public function getIsPassAttribute(): bool
    {
        return $this->result === 'pass';
    }

    public function getIsFailAttribute(): bool
    {
        return $this->result === 'fail';
    }

    public function getRequiresCapaAttribute(): bool
    {
        return $this->result === 'fail' && ! $this->capaAction()->exists();
    }

    public function getHasCapaAttribute(): bool
    {
        return $this->capaAction()->exists();
    }

    // Static methods
    public static function getResults(): array
    {
        return [
            'pass' => 'Pass',
            'fail' => 'Fail',
            'maintenance_required' => 'Maintenance Required',
        ];
    }

    // ProvidesAuditContext implementation
    public function auditContext(): array
    {
        return [
            'module_name' => 'asset_inspection',
            'reference_id' => $this->id,
        ];
    }
}
