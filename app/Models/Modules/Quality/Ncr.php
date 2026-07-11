<?php

namespace App\Models\Modules\Quality;

use App\Models\Core\MasterData\Department;
use App\Models\Core\MasterData\Severity;
use App\Models\Core\MasterData\Site;
use App\Models\Modules\CAPA\CapaAction;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Ncr extends Model
{
    use HasFactory;

    protected $fillable = [
        'ncr_number', 'title', 'source', 'description', 'site_id', 'department_id',
        'product_service', 'batch_lot', 'customer_name', 'severity_id', 'status',
        'root_cause', 'corrective_action', 'preventive_action', 'capa_action_id', 'closed_at',
    ];

    protected $casts = ['closed_at' => 'datetime'];

    public function site(): BelongsTo { return $this->belongsTo(Site::class); }
    public function department(): BelongsTo { return $this->belongsTo(Department::class); }
    public function severity(): BelongsTo { return $this->belongsTo(Severity::class); }
    public function capaAction(): BelongsTo { return $this->belongsTo(CapaAction::class); }

    public function scopeForSite($query, int $siteId) { return $query->where('site_id', $siteId); }
    public function scopeBySource($query, string $source) { return $query->where('source', $source); }
    public function scopeByStatus($query, string $status) { return $query->where('status', $status); }
    public function scopeOpen($query) { return $query->whereIn('status', ['open', 'under_review', 'in_progress']); }
    public function scopeClosed($query) { return $query->where('status', 'closed'); }

    public function isOpen(): bool { return in_array($this->status, ['open', 'under_review', 'in_progress']); }
    public function isClosed(): bool { return $this->status === 'closed'; }
    public function canBeEdited(): bool { return $this->status !== 'closed' && $this->status !== 'rejected'; }

    public static function getSources(): array
    {
        return [
            'internal' => 'Internal',
            'external' => 'External',
            'customer_complaint' => 'Customer Complaint',
            'audit' => 'Audit',
            'supplier' => 'Supplier',
        ];
    }

    public static function getStatuses(): array
    {
        return [
            'open' => 'Open',
            'under_review' => 'Under Review',
            'in_progress' => 'In Progress',
            'closed' => 'Closed',
            'rejected' => 'Rejected',
        ];
    }
}
