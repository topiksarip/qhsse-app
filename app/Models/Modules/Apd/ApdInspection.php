<?php

namespace App\Models\Modules\Apd;

use App\Models\Concerns\Auditable;
use App\Models\Contracts\ProvidesAuditContext;
use App\Models\Core\Files\ManagedFile;
use App\Models\User;
use App\Models\Modules\Apd\ApdItem;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class ApdInspection extends Model implements ProvidesAuditContext
{
    use Auditable, HasFactory, SoftDeletes;

    protected $table = 'apd_inspections';

    protected $fillable = [
        'apd_item_id',
        'inspection_type',
        'inspected_by',
        'inspection_date',
        'result',
        'condition',
        'next_inspection_date',
        'notes',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'inspection_date' => 'date',
        'next_inspection_date' => 'date',
    ];

    // Relationships
    public function item(): BelongsTo
    {
        return $this->belongsTo(ApdItem::class, 'apd_item_id');
    }

    public function inspector(): BelongsTo
    {
        return $this->belongsTo(User::class, 'inspected_by');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function files(): HasMany
    {
        return $this->hasMany(ManagedFile::class, 'reference_id')
            ->where('module_name', 'apd')
            ->where('collection', 'inspection');
    }

    // Scopes
    public function scopeUnfit(Builder $query): Builder
    {
        return $query->where('result', 'tidak_layak');
    }

    public function scopeByResult(Builder $query, string $result): Builder
    {
        return $query->where('result', $result);
    }

    // Accessors
    public function getIsUnfitAttribute(): bool
    {
        return $this->result === 'tidak_layak';
    }

    // Static helpers
    public static function getInspectionTypes(): array
    {
        return [
            'scheduled' => 'Terjadwal',
            'incidental' => 'Insidental',
            'manual' => 'Manual',
        ];
    }

    public static function getResults(): array
    {
        return [
            'layak' => 'Layak Pakai',
            'tidak_layak' => 'Tidak Layak',
        ];
    }

    // ProvidesAuditContext
    public function auditContext(): array
    {
        return [
            'module_name' => 'apd',
            'reference_id' => $this->id,
        ];
    }
}
