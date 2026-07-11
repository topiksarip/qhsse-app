<?php

namespace App\Models\Modules\Audit;

use App\Models\Concerns\Auditable;
use App\Models\Contracts\ProvidesAuditContext;
use App\Models\Core\MasterData\Department;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Audit extends Model implements ProvidesAuditContext
{
    use Auditable, HasFactory;

    protected $table = 'audits';

    protected $fillable = [
        'audit_number', 'title', 'audit_type', 'scope',
        'department_id', 'lead_auditor_id',
        'scheduled_date', 'start_date', 'end_date', 'report_date', 'close_date',
        'status', 'summary', 'created_by',
    ];

    protected function casts(): array
    {
        return [
            'scheduled_date' => 'date',
            'start_date' => 'date',
            'end_date' => 'date',
            'report_date' => 'date',
            'close_date' => 'date',
        ];
    }

    /** @return BelongsTo<Department, self> */
    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    /** @return BelongsTo<User, self> */
    public function leadAuditor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'lead_auditor_id');
    }

    /** @return BelongsTo<User, self> */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /** @return HasMany<AuditFinding> */
    public function findings(): HasMany
    {
        return $this->hasMany(AuditFinding::class, 'audit_id');
    }

    public function auditContext(): array
    {
        return ['module_name' => 'audit', 'reference_id' => $this->getKey()];
    }

    public function allFindingsClosed(): bool
    {
        return $this->findings()->where('status', '!=', 'closed')->doesntExist();
    }

    public function majorFindingsHaveCapa(): bool
    {
        return $this->findings()
            ->where('classification', 'major')
            ->whereNull('capa_action_id')
            ->doesntExist();
    }
}
