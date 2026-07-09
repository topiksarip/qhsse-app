<?php

namespace App\Models\Core\MasterData;

use App\Models\Concerns\Auditable;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Site extends Model
{
    /** @use HasFactory<\Database\Factories\Core\MasterData\SiteFactory> */
    use HasFactory, Auditable;

    protected $fillable = ['code', 'name', 'address', 'is_active'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    /** @return HasMany<Area> */
    public function areas(): HasMany
    {
        return $this->hasMany(Area::class);
    }

    /** @return HasMany<Department> */
    public function departments(): HasMany
    {
        return $this->hasMany(Department::class);
    }
}
