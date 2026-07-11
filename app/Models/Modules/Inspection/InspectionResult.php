<?php

namespace App\Models\Modules\Inspection;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InspectionResult extends Model
{
    use HasFactory;

    protected $fillable = ['inspection_id', 'inspection_item_id', 'answer', 'remark', 'is_unsafe'];

    protected function casts(): array
    {
        return ['is_unsafe' => 'boolean'];
    }

    /** @return BelongsTo<Inspection, InspectionResult> */
    public function inspection(): BelongsTo { return $this->belongsTo(Inspection::class); }

    /** @return BelongsTo<InspectionItem, InspectionResult> */
    public function item(): BelongsTo { return $this->belongsTo(InspectionItem::class, 'inspection_item_id'); }
}
