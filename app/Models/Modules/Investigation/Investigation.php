<?php

namespace App\Models\Modules\Investigation;

use App\Models\Concerns\Auditable;
use App\Models\Modules\Incident\IncidentReport;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Investigation extends Model
{
    /** @use HasFactory<\Database\Factories\Modules\Investigation\InvestigationFactory> */
    use HasFactory, Auditable;

    protected $table = 'investigations';

    protected $fillable = [
        'investigation_number',
        'incident_id',
        'title',
        'status',
        'root_cause',
        'five_whys',
        'fishbone',
        'contributing_factors',
        'timeline_events',
        'recommendations',
        'investigator_id',
        'started_at',
        'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'five_whys' => 'array',
            'fishbone' => 'array',
            'contributing_factors' => 'array',
            'timeline_events' => 'array',
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<IncidentReport, Investigation> */
    public function incident(): BelongsTo
    {
        return $this->belongsTo(IncidentReport::class, 'incident_id');
    }

    /** @return BelongsTo<User, Investigation> */
    public function investigator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'investigator_id');
    }

    /** @return BelongsToMany<User> */
    public function teamMembers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'investigation_team')
            ->withPivot('role')
            ->withTimestamps();
    }
}
