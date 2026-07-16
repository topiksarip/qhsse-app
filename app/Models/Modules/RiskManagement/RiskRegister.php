<?php

declare(strict_types=1);

namespace App\Models\Modules\RiskManagement;

use App\Models\Core\MasterData\Area;
use App\Models\Core\MasterData\Department;
use App\Models\Core\MasterData\RiskMatrixLevel;
use App\Models\Core\MasterData\Severity;
use App\Models\Core\MasterData\Site;
use App\Models\Modules\Apd\ApdRequirement;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RiskRegister extends Model
{
    use HasFactory;

    protected $table = 'risk_registers';

    protected $fillable = [
        'register_number',
        'title',
        'type',
        'site_id',
        'area_id',
        'department_id',
        'activity',
        'hazard',
        'existing_controls',
        'severity_id',
        'probability_id',
        'risk_level_id',
        'additional_controls',
        'residual_severity_id',
        'residual_probability_id',
        'residual_risk_level_id',
        'owner_id',
        'status',
        'review_date',
    ];

    protected $casts = [
        'review_date' => 'date',
        'site_id' => 'integer',
        'area_id' => 'integer',
        'department_id' => 'integer',
        'severity_id' => 'integer',
        'probability_id' => 'integer',
        'risk_level_id' => 'integer',
        'residual_severity_id' => 'integer',
        'residual_probability_id' => 'integer',
        'residual_risk_level_id' => 'integer',
        'owner_id' => 'integer',
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

    public function severity(): BelongsTo
    {
        return $this->belongsTo(Severity::class, 'severity_id');
    }

    public function residualSeverity(): BelongsTo
    {
        return $this->belongsTo(Severity::class, 'residual_severity_id');
    }

    public function riskLevel(): BelongsTo
    {
        return $this->belongsTo(RiskMatrixLevel::class, 'risk_level_id');
    }

    public function residualRiskLevel(): BelongsTo
    {
        return $this->belongsTo(RiskMatrixLevel::class, 'residual_risk_level_id');
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function apdRequirements(): HasMany
    {
        return $this->hasMany(ApdRequirement::class, 'risk_register_id');
    }

    // Shared polymorphic relations (via module_name + reference_id)
    public function files()
    {
        return $this->morphMany(\App\Models\Core\ManagedFile::class, 'reference')
            ->where('module_name', 'risk');
    }

    public function comments()
    {
        return $this->morphMany(\App\Models\Core\Comment::class, 'reference')
            ->where('module_name', 'risk');
    }

    public function activities()
    {
        return $this->morphMany(\App\Models\Core\ActivityLog::class, 'reference')
            ->where('module_name', 'risk');
    }

    public function audits()
    {
        return $this->morphMany(\App\Models\Core\AuditLog::class, 'reference')
            ->where('module_name', 'risk');
    }

    // Helper methods
    public function isObsolete(): bool
    {
        return $this->status === 'obsolete';
    }

    public function canBeAssessed(): bool
    {
        return $this->status === 'identified';
    }

    public function canNeedControls(): bool
    {
        return $this->status === 'assessed';
    }

    public function canImplementControls(): bool
    {
        return $this->status === 'controls_needed';
    }

    public function canBeMonitored(): bool
    {
        return $this->status === 'controls_in_place';
    }

    public function getRiskLevelColorAttribute(): ?string
    {
        if (!$this->riskLevel) {
            return null;
        }

        return match ($this->riskLevel->risk_level) {
            'RED' => 'red',
            'ORANGE' => 'orange',
            'YELLOW' => 'yellow',
            'GREEN' => 'green',
            default => 'gray',
        };
    }

    public function getResidualRiskLevelColorAttribute(): ?string
    {
        if (!$this->residualRiskLevel) {
            return null;
        }

        return match ($this->residualRiskLevel->risk_level) {
            'RED' => 'red',
            'ORANGE' => 'orange',
            'YELLOW' => 'yellow',
            'GREEN' => 'green',
            default => 'gray',
        };
    }
}
