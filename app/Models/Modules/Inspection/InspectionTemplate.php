<?php

namespace App\Models\Modules\Inspection;

use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class InspectionTemplate extends Model
{
    use HasFactory, Auditable;

    protected $fillable = ['code', 'name', 'description', 'category', 'is_active'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    /** @return HasMany<InspectionItem> */
    public function items(): HasMany
    {
        return $this->hasMany(InspectionItem::class)->orderBy('order');
    }

    /** @return HasMany<Inspection> */
    public function inspections(): HasMany
    {
        return $this->hasMany(Inspection::class);
    }
}
