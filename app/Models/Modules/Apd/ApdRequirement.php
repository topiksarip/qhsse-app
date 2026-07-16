<?php

namespace App\Models\Modules\Apd;

use App\Models\Concerns\Auditable;
use App\Models\Core\MasterData\ApdCatalog;
use App\Models\Modules\RiskManagement\RiskRegister;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class ApdRequirement extends Model
{
    use Auditable, HasFactory, SoftDeletes;

    protected $table = 'apd_requirements';

    protected $fillable = [
        'risk_register_id',
        'apd_catalog_id',
        'quantity',
        'notes',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'quantity' => 'integer',
    ];

    public function riskRegister(): BelongsTo
    {
        return $this->belongsTo(RiskRegister::class);
    }

    public function catalog(): BelongsTo
    {
        return $this->belongsTo(ApdCatalog::class, 'apd_catalog_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
