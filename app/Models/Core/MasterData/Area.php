<?php

namespace App\Models\Core\MasterData;

use App\Models\Concerns\Auditable;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Area extends Model
{
    /** @use HasFactory<\Database\Factories\Core\MasterData\AreaFactory> */
    use HasFactory, Auditable;

    protected $fillable = ['site_id', 'code', 'name', 'type', 'is_active'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    /** @return BelongsTo<Site, Area> */
    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class);
    }
}
