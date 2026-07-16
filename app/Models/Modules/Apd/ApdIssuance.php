<?php

namespace App\Models\Modules\Apd;

use App\Models\Concerns\Auditable;
use App\Models\Contracts\ProvidesAuditContext;
use App\Models\Core\MasterData\Area;
use App\Models\Core\MasterData\Site;
use App\Models\Core\Users\Employee;
use App\Models\Modules\Contractor\Contractor;
use App\Models\User;
use Illuminate\Database\Eloquent\Relations\Relation;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class ApdIssuance extends Model implements ProvidesAuditContext
{
    use Auditable, HasFactory, SoftDeletes;

    protected $table = 'apd_issuances';

    protected $fillable = [
        'issue_number',
        'apd_item_id',
        'quantity',
        'holder_type',
        'holder_id',
        'requested_by',
        'approved_by',
        'issued_by',
        'returned_by',
        'requested_date',
        'issue_date',
        'expected_return_date',
        'returned_date',
        'expiry_date',
        'status',
        'condition_out',
        'condition_in',
        'notes',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'requested_date' => 'date',
        'issue_date' => 'date',
        'expected_return_date' => 'date',
        'returned_date' => 'date',
        'expiry_date' => 'date',
    ];

    protected static function booted(): void
    {
        Relation::morphMap([
            'employee' => Employee::class,
            'contractor' => Contractor::class,
            'location' => Area::class,
        ], false);
    }

    // Holder type constants
    public const HOLDER_EMPLOYEE = 'employee';
    public const HOLDER_CONTRACTOR = 'contractor';
    public const HOLDER_LOCATION = 'location';

    public static array $holderTypes = [
        self::HOLDER_EMPLOYEE => 'Karyawan',
        self::HOLDER_CONTRACTOR => 'Kontraktor',
        self::HOLDER_LOCATION => 'Lokasi',
    ];

    public static array $statuses = [
        'draft' => 'Draft',
        'requested' => 'Diminta',
        'approved' => 'Disetujui',
        'issued' => 'Terissue',
        'returned' => 'Dikembalikan',
        'disposed' => 'Dimusnahkan',
        'rejected' => 'Ditolak',
    ];

    public static array $conditions = [
        'new' => 'Baru',
        'good' => 'Baik',
        'fair' => 'Cukup',
        'poor' => 'Buruk',
        'damaged' => 'Rusak',
    ];

    // Relationships
    public function item(): BelongsTo
    {
        return $this->belongsTo(ApdItem::class, 'apd_item_id');
    }

    public function requestedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function issuedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'issued_by');
    }

    public function returnedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'returned_by');
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

    // Accessors
    public function getHolderLabelAttribute(): string
    {
        return self::$holderTypes[$this->holder_type] ?? $this->holder_type;
    }

    public function getStatusLabelAttribute(): string
    {
        return self::$statuses[$this->status] ?? $this->status;
    }

    // Helpers
    public function markStockEffectDone(): bool
    {
        return in_array($this->status, ['issued', 'returned', 'disposed'], true);
    }

    // Scopes
    public function scopeByStatus(Builder $query, string $status): Builder
    {
        return $query->where('status', $status);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->whereIn('status', ['issued', 'approved', 'requested']);
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
