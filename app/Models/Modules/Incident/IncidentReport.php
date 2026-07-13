<?php

namespace App\Models\Modules\Incident;

use App\Models\Concerns\Auditable;
use App\Models\Core\MasterData\Area;
use App\Models\Core\MasterData\Department;
use App\Models\Core\MasterData\Priority;
use App\Models\Core\MasterData\Severity;
use App\Models\Core\MasterData\Site;
use App\Models\Core\Users\Employee;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class IncidentReport extends Model
{
    /** @use HasFactory<\Database\Factories\Modules\Incident\IncidentReportFactory> */
    use HasFactory, Auditable;

    protected $table = 'incidents';

    protected $fillable = [
        'incident_number',
        'title',
        'category',
        'occurred_at',
        'site_id',
        'area_id',
        'department_id',
        'reporter_id',
        'severity_id',
        'priority_id',
        'description',
        'immediate_action',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'occurred_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<Site, IncidentReport> */
    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class);
    }

    /** @return BelongsTo<Area, IncidentReport> */
    public function area(): BelongsTo
    {
        return $this->belongsTo(Area::class);
    }

    /** @return BelongsTo<Department, IncidentReport> */
    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    /** @return BelongsTo<User, IncidentReport> */
    public function reporter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reporter_id');
    }

    /** @return BelongsTo<Severity, IncidentReport> */
    public function severity(): BelongsTo
    {
        return $this->belongsTo(Severity::class);
    }

    /** @return BelongsTo<Priority, IncidentReport> */
    public function priority(): BelongsTo
    {
        return $this->belongsTo(Priority::class);
    }

    /** @return BelongsToMany<Employee> */
    public function involvedPersons(): BelongsToMany
    {
        return $this->belongsToMany(Employee::class, 'incident_involved_persons', 'incident_id', 'employee_id')
            ->withPivot('note')
            ->withTimestamps();
    }
}
