<?php

namespace App\Models\Modules\Capa;

use App\Models\Concerns\Auditable;
use App\Models\Core\MasterData\Department;
use App\Models\Core\MasterData\Priority;
use App\Models\Core\MasterData\Severity;
use App\Models\Core\MasterData\Site;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CapaAction extends Model
{
    use HasFactory, Auditable;

    protected $table = 'capa_actions';

    protected $fillable = [
        'action_number', 'title', 'description',
        'source_module', 'source_reference_id', 'source_type',
        'site_id', 'department_id',
        'assigned_to', 'assigned_by', 'assigned_at',
        'due_date', 'severity_id', 'priority_id',
        'status', 'verification_note',
        'verified_by', 'verified_at', 'closed_at',
    ];

    protected function casts(): array
    {
        return [
            'assigned_at' => 'datetime',
            'due_date' => 'date',
            'verified_at' => 'datetime',
            'closed_at' => 'datetime',
        ];
    }

    public function site(): BelongsTo { return $this->belongsTo(Site::class); }
    public function department(): BelongsTo { return $this->belongsTo(Department::class); }
    public function assignedTo(): BelongsTo { return $this->belongsTo(User::class, 'assigned_to'); }
    public function assignedBy(): BelongsTo { return $this->belongsTo(User::class, 'assigned_by'); }
    public function severity(): BelongsTo { return $this->belongsTo(Severity::class); }
    public function priority(): BelongsTo { return $this->belongsTo(Priority::class); }
    public function verifiedBy(): BelongsTo { return $this->belongsTo(User::class, 'verified_by'); }

    public function getIsOverdueAttribute(): bool
    {
        return $this->due_date !== null
            && $this->due_date < now()->toDateString()
            && !in_array($this->status, ['closed', 'rejected']);
    }
}
