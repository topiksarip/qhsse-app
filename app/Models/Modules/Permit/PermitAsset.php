<?php

namespace App\Models\Modules\Permit;

use App\Models\Modules\Asset\Asset;
use App\Models\Modules\Permit\Permit;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Pivot row linking a Permit to an Asset (alat/peralatan) used in the work.
 */
class PermitAsset extends Model
{
    use HasFactory;

    protected $fillable = ['permit_id', 'asset_id', 'role'];

    /** @return BelongsTo<Permit, PermitAsset> */
    public function permit(): BelongsTo
    {
        return $this->belongsTo(Permit::class);
    }

    /** @return BelongsTo<Asset, PermitAsset> */
    public function asset(): BelongsTo
    {
        return $this->belongsTo(Asset::class);
    }
}
