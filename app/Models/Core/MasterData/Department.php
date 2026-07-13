<?php

namespace App\Models\Core\MasterData;

use App\Models\Concerns\Auditable;
use Database\Factories\Core\MasterData\DepartmentFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Department extends Model
{
    /** @use HasFactory<DepartmentFactory> */
    use Auditable, HasFactory;

    protected $fillable = ['site_id', 'code', 'name', 'is_active'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    /** @return BelongsTo<Site, Department> */
    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class);
    }

    /** @return HasMany<Position> */
    public function positions(): HasMany
    {
        return $this->hasMany(Position::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
