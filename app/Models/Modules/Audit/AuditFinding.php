<?php

namespace App\Models\Modules\Audit;

use App\Models\Concerns\Auditable;
use App\Models\Contracts\ProvidesAuditContext;
use App\Models\Modules\Capa\CapaAction;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AuditFinding extends Model implements ProvidesAuditContext
{
    use Auditable, HasFactory;

    protected $table = 'audit_findings';

    protected $fillable = [
        'audit_id', 'finding_number', 'classification', 'description',
        'recommendation', 'capa_action_id', 'status',
        'due_date', 'closed_date', 'closed_by',
    ];

    protected function casts(): array
    {
        return [
            'due_date' => 'date',
            'closed_date' => 'date',
        ];
    }

    /** @return BelongsTo<Audit, self> */
    public function audit(): BelongsTo
    {
        return $this->belongsTo(Audit::class);
    }

    /** @return BelongsTo<CapaAction, self> */
    public function capaAction(): BelongsTo
    {
        return $this->belongsTo(CapaAction::class, 'capa_action_id');
    }

    /** @return BelongsTo<User, self> */
    public function closedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'closed_by');
    }

    public function auditContext(): array
    {
        return ['module_name' => 'audit_finding', 'reference_id' => $this->getKey()];
    }
}
