<?php

namespace App\Models\Modules\Asset;

use App\Models\Concerns\Auditable;
use App\Models\Contracts\ProvidesAuditContext;
use App\Models\Core\Files\ManagedFile;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AssetCertificate extends Model implements ProvidesAuditContext
{
    use Auditable, HasFactory;

    protected $fillable = [
        'asset_id',
        'certificate_type',
        'certificate_number',
        'issuing_body',
        'issued_date',
        'expiry_date',
        'status',
        'certificate_file_id',
        'notes',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'issued_date' => 'date',
        'expiry_date' => 'date',
    ];

    // Relationships
    public function asset(): BelongsTo
    {
        return $this->belongsTo(Asset::class);
    }

    public function certificateFile(): BelongsTo
    {
        return $this->belongsTo(ManagedFile::class, 'certificate_file_id');
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

    public function scopeExpired(Builder $query): Builder
    {
        return $query->where('status', 'expired');
    }

    public function scopeExpiringSoon(Builder $query): Builder
    {
        return $query->where('status', 'expiring_soon');
    }

    public function scopeExpiringCritical(Builder $query): Builder
    {
        return $query->where('status', 'expiring_critical');
    }

    public function scopeValid(Builder $query): Builder
    {
        return $query->where('status', 'valid');
    }

    public function scopeByAsset(Builder $query, int $assetId): Builder
    {
        return $query->where('asset_id', $assetId);
    }

    // Accessors
    public function getIsExpiredAttribute(): bool
    {
        return $this->status === 'expired';
    }

    public function getIsExpiringAttribute(): bool
    {
        return in_array($this->status, ['expiring_soon', 'expiring_critical']);
    }

    public function getDaysUntilExpiryAttribute(): ?int
    {
        if (! $this->expiry_date) {
            return null;
        }

        return now()->diffInDays($this->expiry_date, false);
    }

    // Methods
    public function updateStatus(): void
    {
        if (! $this->expiry_date) {
            $this->status = 'valid';
            $this->save();

            return;
        }

        $daysUntilExpiry = $this->days_until_expiry;

        if ($daysUntilExpiry < 0) {
            $this->status = 'expired';
        } elseif ($daysUntilExpiry <= 7) {
            $this->status = 'expiring_critical';
        } elseif ($daysUntilExpiry <= 30) {
            $this->status = 'expiring_soon';
        } else {
            $this->status = 'valid';
        }

        $this->save();
    }

    // Static methods
    public static function getStatuses(): array
    {
        return [
            'valid' => 'Valid',
            'expiring_soon' => 'Expiring Soon',
            'expiring_critical' => 'Expiring Critical',
            'expired' => 'Expired',
        ];
    }

    // ProvidesAuditContext implementation
    public function auditContext(): array
    {
        return [
            'module_name' => 'asset_certificate',
            'reference_id' => $this->id,
        ];
    }
}
