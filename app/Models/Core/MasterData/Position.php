<?php

namespace App\Models\Core\MasterData;

use App\Models\Concerns\Auditable;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Position extends Model
{
    /** @use HasFactory<\Database\Factories\Core\MasterData\PositionFactory> */
    use HasFactory, Auditable;

    protected $fillable = ['department_id', 'code', 'name', 'is_active'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    /** @return BelongsTo<Department, Position> */
    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }
}
