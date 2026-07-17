<?php

namespace App\Models\Modules\Inspection;

use App\Models\Core\Files\ManagedFile;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InspectionResult extends Model
{
    use HasFactory;

    protected $fillable = ['inspection_id', 'inspection_item_id', 'inspection_unit_id', 'answer', 'remark', 'is_unsafe', 'photo'];

    protected function casts(): array
    {
        return ['is_unsafe' => 'boolean'];
    }

    /** @return BelongsTo<Inspection, InspectionResult> */
    public function inspection(): BelongsTo { return $this->belongsTo(Inspection::class); }

    /** @return BelongsTo<InspectionItem, InspectionResult> */
    public function item(): BelongsTo { return $this->belongsTo(InspectionItem::class, 'inspection_item_id'); }

    /** @return BelongsTo<InspectionUnit, InspectionResult> */
    public function unit(): BelongsTo { return $this->belongsTo(InspectionUnit::class, 'inspection_unit_id'); }

    /** @return BelongsTo<ManagedFile, InspectionResult> */
    public function photoFile(): BelongsTo
    {
        return $this->belongsTo(ManagedFile::class, 'photo', 'path')
            ->where('collection', 'inspection_result');
    }
}
