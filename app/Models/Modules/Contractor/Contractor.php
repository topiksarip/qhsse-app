<?php

namespace App\Models\Modules\Contractor;

use App\Models\User;
use App\Models\Contracts\ProvidesAuditContext;
use App\Models\Core\MasterData\Area;
use App\Models\Core\MasterData\Site;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Contractor extends Model implements ProvidesAuditContext
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'contractor_number',
        'company_name',
        'business_registration_number',
        'tax_id',
        'contact_person',
        'contact_phone',
        'contact_email',
        'address',
        'business_type',
        'scope_of_work',
        'specialization',
        'contract_start_date',
        'contract_end_date',
        'contract_status',
        'contract_terms',
        'safety_induction_required',
        'safety_induction_date',
        'safety_induction_expiry',
        'insurance_required',
        'insurance_policy_number',
        'insurance_expiry',
        'performance_rating',
        'incident_count',
        'violation_count',
        'performance_notes',
        'authorized_sites',
        'authorized_areas',
        'document_files',
        'approval_status',
        'approved_by',
        'approved_at',
        'approval_notes',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'contract_start_date' => 'date',
        'contract_end_date' => 'date',
        'safety_induction_date' => 'date',
        'safety_induction_expiry' => 'date',
        'insurance_expiry' => 'date',
        'approved_at' => 'datetime',
        'safety_induction_required' => 'boolean',
        'insurance_required' => 'boolean',
        'performance_rating' => 'decimal:2',
        'incident_count' => 'integer',
        'violation_count' => 'integer',
        'authorized_sites' => 'array',
        'authorized_areas' => 'array',
        'document_files' => 'array',
    ];

    // Relationships
    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    // Accessors
    public function getIsActiveAttribute(): bool
    {
        return $this->contract_status === 'active';
    }

    public function getIsExpiredAttribute(): bool
    {
        if (!$this->contract_end_date) {
            return false;
        }
        return $this->contract_end_date->isPast();
    }

    public function getIsApprovedAttribute(): bool
    {
        return $this->approval_status === 'approved';
    }

    public function getIsSafetyInductionValidAttribute(): bool
    {
        if (!$this->safety_induction_required) {
            return true;
        }
        if (!$this->safety_induction_expiry) {
            return false;
        }
        return $this->safety_induction_expiry->isFuture();
    }

    public function getIsInsuranceValidAttribute(): bool
    {
        if (!$this->insurance_required) {
            return true;
        }
        if (!$this->insurance_expiry) {
            return false;
        }
        return $this->insurance_expiry->isFuture();
    }

    public function getContractStatusLabelAttribute(): string
    {
        return match($this->contract_status) {
            'pending' => 'Pending',
            'active' => 'Active',
            'suspended' => 'Suspended',
            'expired' => 'Expired',
            'terminated' => 'Terminated',
            default => 'Unknown',
        };
    }

    public function getApprovalStatusLabelAttribute(): string
    {
        return match($this->approval_status) {
            'draft' => 'Draft',
            'submitted' => 'Submitted',
            'approved' => 'Approved',
            'rejected' => 'Rejected',
            default => 'Unknown',
        };
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('contract_status', 'active');
    }

    public function scopeApproved($query)
    {
        return $query->where('approval_status', 'approved');
    }

    public function scopeExpiringSoon($query, $days = 30)
    {
        return $query->where('contract_end_date', '<=', now()->addDays($days))
            ->where('contract_end_date', '>=', now());
    }

    public function scopeSafetyInductionExpiring($query, $days = 30)
    {
        return $query->where('safety_induction_required', true)
            ->where('safety_induction_expiry', '<=', now()->addDays($days))
            ->where('safety_induction_expiry', '>=', now());
    }

    public function scopeInsuranceExpiring($query, $days = 30)
    {
        return $query->where('insurance_required', true)
            ->where('insurance_expiry', '<=', now()->addDays($days))
            ->where('insurance_expiry', '>=', now());
    }

    // Static methods
    public static function getContractStatuses(): array
    {
        return [
            'pending' => 'Pending',
            'active' => 'Active',
            'suspended' => 'Suspended',
            'expired' => 'Expired',
            'terminated' => 'Terminated',
        ];
    }

    // ProvidesAuditContext implementation
    public function auditContext(): array
    {
        return [
            'module_name' => 'contractor',
            'reference_id' => $this->id,
        ];
    }

    public static function getApprovalStatuses(): array
    {
        return [
            'draft' => 'Draft',
            'submitted' => 'Submitted',
            'approved' => 'Approved',
            'rejected' => 'Rejected',
        ];
    }

    public static function getBusinessTypes(): array
    {
        return [
            'construction' => 'Construction',
            'maintenance' => 'Maintenance',
            'cleaning' => 'Cleaning & Janitorial',
            'security' => 'Security Services',
            'transportation' => 'Transportation',
            'consulting' => 'Consulting',
            'technical' => 'Technical Services',
            'catering' => 'Catering',
            'other' => 'Other',
        ];
    }
}
