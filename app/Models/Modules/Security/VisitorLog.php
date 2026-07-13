<?php

namespace App\Models\Modules\Security;

use App\Models\Core\MasterData\Site;
use App\Models\Core\Users\Employee;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VisitorLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'visitor_name',
        'visitor_type',
        'visitor_id_number',
        'visitor_company',
        'visitor_phone',
        'host_employee_id',
        'site_id',
        'purpose',
        'vehicle_number',
        'checked_in_at',
        'checked_out_at',
        'checked_in_by',
        'checked_out_by',
        'status',
        'notes',
    ];

    protected $casts = [
        'checked_in_at' => 'datetime',
        'checked_out_at' => 'datetime',
    ];

    // Relationships
    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class);
    }

    public function hostEmployee(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'host_employee_id');
    }

    public function checkedInBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'checked_in_by');
    }

    public function checkedOutBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'checked_out_by');
    }

    // Scopes
    public function scopeForSite($query, int $siteId)
    {
        return $query->where('site_id', $siteId);
    }

    public function scopeByType($query, string $type)
    {
        return $query->where('visitor_type', $type);
    }

    public function scopeCheckedIn($query)
    {
        return $query->where('status', 'checked_in')->whereNull('checked_out_at');
    }

    public function scopeCheckedOut($query)
    {
        return $query->where('status', 'checked_out')->whereNotNull('checked_out_at');
    }

    // Helpers
    public function isCheckedIn(): bool
    {
        return $this->status === 'checked_in' && $this->checked_out_at === null;
    }

    public function isCheckedOut(): bool
    {
        return $this->status === 'checked_out' && $this->checked_out_at !== null;
    }

    public static function getVisitorTypes(): array
    {
        return [
            'KTP' => 'KTP',
            'SIM' => 'SIM',
            'Passport' => 'Passport',
            'Lainnya' => 'Lainnya',
        ];
    }

    public function getVisitorTypeLabel(): string
    {
        return self::getVisitorTypes()[$this->visitor_type] ?? $this->visitor_type;
    }
}
