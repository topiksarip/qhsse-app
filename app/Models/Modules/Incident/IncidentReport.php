<?php

namespace App\Models\Modules\Incident;

use App\Models\Concerns\Auditable;
use App\Models\Core\MasterData\Area;
use App\Models\Core\MasterData\Category;
use App\Models\Core\MasterData\Company;
use App\Models\Core\MasterData\Department;
use App\Models\Core\MasterData\Priority;
use App\Models\Core\MasterData\Severity;
use App\Models\Core\MasterData\Site;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class IncidentReport extends Model
{
    /** @use HasFactory<\Database\Factories\Modules\Incident\IncidentReportFactory> */
    use HasFactory, Auditable, SoftDeletes;

    protected $table = '02_incident_reporting';

    protected $fillable = [
        'number', 'title', 'description',
        'site_id', 'area_id', 'department_id', 'company_id',
        'category_id', 'severity_id', 'priority_id',
        'reporter_id', 'assigned_to', 'reviewer_id', 'approver_id', 'verifier_id',
        'event_date', 'due_date', 'status', 'meta',
        'created_by', 'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'event_date' => 'date',
            'due_date' => 'date',
            'meta' => 'array',
        ];
    }

    public const STATUSES = [
        'draft', 'submitted', 'under_review', 'in_progress',
        'approved', 'waiting_verification', 'closed', 'rejected', 'cancelled',
    ];

    public function site(): BelongsTo { return $this->belongsTo(Site::class); }
    public function area(): BelongsTo { return $this->belongsTo(Area::class); }
    public function department(): BelongsTo { return $this->belongsTo(Department::class); }
    public function company(): BelongsTo { return $this->belongsTo(Company::class); }
    public function category(): BelongsTo { return $this->belongsTo(Category::class); }
    public function severity(): BelongsTo { return $this->belongsTo(Severity::class); }
    public function priority(): BelongsTo { return $this->belongsTo(Priority::class); }
    public function reporter(): BelongsTo { return $this->belongsTo(User::class, 'reporter_id'); }
    public function assignee(): BelongsTo { return $this->belongsTo(User::class, 'assigned_to'); }
    public function reviewer(): BelongsTo { return $this->belongsTo(User::class, 'reviewer_id'); }
    public function approver(): BelongsTo { return $this->belongsTo(User::class, 'approver_id'); }
    public function verifier(): BelongsTo { return $this->belongsTo(User::class, 'verifier_id'); }
    public function creator(): BelongsTo { return $this->belongsTo(User::class, 'created_by'); }
    public function updater(): BelongsTo { return $this->belongsTo(User::class, 'updated_by'); }

    public function scopeVisibleTo(Builder $query, User $user): Builder
    {
        if ($user->can('incident-reporting.view.all')) {
            return $query;
        }
        return $query->where(function (Builder $q) use ($user) {
            $q->where('reporter_id', $user->id)
              ->orWhere('assigned_to', $user->id)
              ->orWhere('created_by', $user->id);
        });
    }
}
