<?php

namespace App\Models\Modules\Inspection;

use App\Models\Concerns\Auditable;
use App\Models\Core\MasterData\Area;
use App\Models\Core\MasterData\Site;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Inspection extends Model
{
    use HasFactory, Auditable;

    protected $fillable = [
        'inspection_number', 'inspection_template_id', 'site_id', 'area_id',
        'inspector_id', 'scheduled_at', 'executed_at', 'status', 'overall_result', 'notes',
    ];

    protected function casts(): array
    {
        return [
            'scheduled_at' => 'date',
            'executed_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<InspectionTemplate, Inspection> */
    public function template(): BelongsTo
    {
        return $this->belongsTo(InspectionTemplate::class, 'inspection_template_id');
    }

    /** @return BelongsTo<Site, Inspection> */
    public function site(): BelongsTo { return $this->belongsTo(Site::class); }

    /** @return BelongsTo<Area, Inspection> */
    public function area(): BelongsTo { return $this->belongsTo(Area::class); }

    /** @return BelongsTo<User, Inspection> */
    public function inspector(): BelongsTo { return $this->belongsTo(User::class, 'inspector_id'); }

    /** @return HasMany<InspectionResult> */
    public function results(): HasMany
    {
        return $this->hasMany(InspectionResult::class);
    }
}
