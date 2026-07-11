<?php

namespace App\Models\Modules\Environment;

use App\Models\Core\MasterData\Area;
use App\Models\Core\MasterData\Site;
use App\Models\Modules\CAPA\CapaAction;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EnvironmentalRecord extends Model
{
    /** @use HasFactory<\Database\Factories\Modules\Environment\EnvironmentalRecordFactory> */
    use HasFactory;

    protected $fillable = [
        'record_number',
        'type',
        'title',
        'description',
        'site_id',
        'area_id',
        'occurred_at',
        'measured_value',
        'unit',
        'limit_value',
        'is_exceedance',
        'waste_type',
        'quantity',
        'disposal_method',
        'material',
        'volume',
        'containment',
        'parameter',
        'location',
        'reporter_id',
        'status',
        'capa_action_id',
    ];

    protected function casts(): array
    {
        return [
            'occurred_at' => 'datetime',
            'measured_value' => 'decimal:4',
            'limit_value' => 'decimal:4',
            'quantity' => 'decimal:4',
            'volume' => 'decimal:4',
            'is_exceedance' => 'boolean',
        ];
    }

    /** @return BelongsTo<Site, EnvironmentalRecord> */
    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class);
    }

    /** @return BelongsTo<Area, EnvironmentalRecord> */
    public function area(): BelongsTo
    {
        return $this->belongsTo(Area::class);
    }

    /** @return BelongsTo<User, EnvironmentalRecord> */
    public function reporter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reporter_id');
    }

    /** @return BelongsTo<CapaAction, EnvironmentalRecord> */
    public function capaAction(): BelongsTo
    {
        return $this->belongsTo(CapaAction::class);
    }

    /**
     * Calculate and set exceedance flag based on measured and limit values
     */
    public function calculateExceedance(): void
    {
        if ($this->measured_value !== null && $this->limit_value !== null) {
            $this->is_exceedance = $this->measured_value > $this->limit_value;
        } else {
            $this->is_exceedance = false;
        }
    }

    /**
     * Get all valid record types
     */
    public static function getTypes(): array
    {
        return [
            'waste' => 'Limbah (Waste)',
            'spill' => 'Tumpahan (Spill)',
            'emission' => 'Emisi (Emission)',
            'noise' => 'Kebisingan (Noise)',
            'water_monitoring' => 'Monitoring Air (Water Monitoring)',
            'other' => 'Lainnya (Other)',
        ];
    }

    /**
     * Get all valid statuses
     */
    public static function getStatuses(): array
    {
        return [
            'recorded' => 'Recorded',
            'investigated' => 'Investigated',
            'action_open' => 'Action Open',
            'closed' => 'Closed',
        ];
    }

    /**
     * Get type-specific required fields
     */
    public static function getRequiredFieldsByType(string $type): array
    {
        return match ($type) {
            'waste' => ['waste_type', 'quantity', 'disposal_method'],
            'spill' => ['material', 'volume', 'containment'],
            'emission' => ['parameter', 'measured_value', 'unit', 'limit_value'],
            'noise' => ['location', 'measured_value', 'unit', 'limit_value'],
            'water_monitoring' => ['parameter', 'measured_value', 'unit', 'limit_value'],
            'other' => [],
        };
    }
}
