<?php

namespace App\Models\Modules\Inspection;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InspectionItem extends Model
{
    use HasFactory;

    protected $fillable = ['inspection_template_id', 'question', 'type', 'category', 'is_required', 'order'];

    protected function casts(): array
    {
        return ['is_required' => 'boolean', 'order' => 'integer'];
    }

    /** @return BelongsTo<InspectionTemplate, InspectionItem> */
    public function template(): BelongsTo
    {
        return $this->belongsTo(InspectionTemplate::class, 'inspection_template_id');
    }
}
