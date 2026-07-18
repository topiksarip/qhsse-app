<?php

namespace App\Models\Modules\Permit;

use App\Models\Core\MasterData\Area;
use App\Models\Core\MasterData\Company;
use App\Models\Core\MasterData\Department;
use App\Models\Core\MasterData\Site;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Permit extends Model
{
    /** @use HasFactory<\Database\Factories\Modules\Permit\PermitFactory> */
    use HasFactory;

    protected $fillable = [
        'permit_number',
        'type',
        'title',
        'description',
        'site_id',
        'area_id',
        'department_id',
        'contractor_id',
        'work_location',
        'work_description',
        'start_datetime',
        'end_datetime',
        'validity_hours',
        'status',
        'risk_level',
        'jsa_reference',
        'approved_by',
        'approved_at',
        'closed_by',
        'closed_at',
        'cancellation_reason',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'start_datetime' => 'datetime',
            'end_datetime' => 'datetime',
            'approved_at' => 'datetime',
            'closed_at' => 'datetime',
            'validity_hours' => 'integer',
        ];
    }

    /** @return BelongsTo<Site, Permit> */
    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class);
    }

    /** @return BelongsTo<Area, Permit> */
    public function area(): BelongsTo
    {
        return $this->belongsTo(Area::class);
    }

    /** @return BelongsTo<Department, Permit> */
    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    /** @return BelongsTo<Company, Permit> */
    public function contractor(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'contractor_id');
    }

    /** @return BelongsTo<User, Permit> */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /** @return BelongsTo<User, Permit> */
    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    /** @return BelongsTo<User, Permit> */
    public function closer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'closed_by');
    }

    /** @return HasMany<PermitChecklist> */
    public function checklists(): HasMany
    {
        return $this->hasMany(PermitChecklist::class);
    }

    /** @return HasMany<PermitAsset> */
    public function permitAssets(): HasMany
    {
        return $this->hasMany(PermitAsset::class);
    }

    /** @return HasMany<PermitWorker> */
    public function permitWorkers(): HasMany
    {
        return $this->hasMany(PermitWorker::class);
    }

    /**
     * Check if permit is currently valid (active and within time window)
     */
    public function isValid(): bool
    {
        return $this->status === 'active'
            && $this->start_datetime->isPast()
            && $this->end_datetime->isFuture();
    }

    /**
     * Check if permit is expiring soon (within 24 hours)
     */
    public function isExpiringSoon(): bool
    {
        return $this->status === 'active'
            && $this->end_datetime->isFuture()
            && $this->end_datetime->diffInHours(now()) <= 24;
    }

    /**
     * Check if permit is expired
     */
    public function isExpired(): bool
    {
        return $this->status === 'active'
            && $this->end_datetime->isPast();
    }

    /**
     * Get all valid permit types
     */
    public static function getTypes(): array
    {
        return [
            'hot_work' => 'Hot Work',
            'working_at_height' => 'Working at Height',
            'confined_space' => 'Confined Space',
            'electrical' => 'Electrical Work',
            'excavation' => 'Excavation',
            'lifting' => 'Lifting Operation',
            'other' => 'Other',
        ];
    }

    /**
     * Get all valid statuses
     */
    public static function getStatuses(): array
    {
        return [
            'draft' => 'Draft',
            'submitted' => 'Submitted',
            'under_review' => 'Under Review',
            'approved' => 'Approved',
            'active' => 'Active',
            'closed' => 'Closed',
            'rejected' => 'Rejected',
        ];
    }

    /**
     * Get all valid risk levels
     */
    public static function getRiskLevels(): array
    {
        return [
            'low' => 'Low',
            'medium' => 'Medium',
            'high' => 'High',
            'critical' => 'Critical',
        ];
    }
}
