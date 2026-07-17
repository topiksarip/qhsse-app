<?php

namespace App\Models\Modules\Inspection;

use App\Models\Modules\Inspection\Inspection;
use App\Models\Modules\Inspection\InspectionResult;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class InspectionUnit extends Model
{
    use HasFactory;

    protected $fillable = ['inspection_id', 'identifier', 'status', 'notes', 'cancelled_reason'];

    protected function casts(): array
    {
        return [];
    }

    /** @return BelongsTo<Inspection, InspectionUnit> */
    public function inspection(): BelongsTo
    {
        return $this->belongsTo(Inspection::class);
    }

    /** @return HasMany<InspectionResult, InspectionUnit> */
    public function results(): HasMany
    {
        return $this->hasMany(InspectionResult::class);
    }

    public function isDone(): bool
    {
        return $this->status === 'done';
    }

    public function isCancelled(): bool
    {
        return $this->status === 'cancelled';
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }
}
