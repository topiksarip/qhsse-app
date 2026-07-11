<?php

namespace App\Models\Modules\Quality;

use App\Models\Core\MasterData\Severity;
use App\Models\Core\MasterData\Site;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CustomerComplaint extends Model
{
    use HasFactory;

    protected $fillable = [
        'complaint_number', 'customer_name', 'customer_contact', 'title', 'description',
        'site_id', 'product_service', 'severity_id', 'status', 'resolution', 'ncr_id', 'closed_at',
    ];

    protected $casts = ['closed_at' => 'datetime'];

    public function site(): BelongsTo { return $this->belongsTo(Site::class); }
    public function severity(): BelongsTo { return $this->belongsTo(Severity::class); }
    public function ncr(): BelongsTo { return $this->belongsTo(Ncr::class); }

    public function scopeForSite($query, int $siteId) { return $query->where('site_id', $siteId); }
    public function scopeByStatus($query, string $status) { return $query->where('status', $status); }
    public function scopeOpen($query) { return $query->where('status', 'open'); }
    public function scopeClosed($query) { return $query->where('status', 'closed'); }

    public function isOpen(): bool { return $this->status === 'open'; }
    public function isClosed(): bool { return $this->status === 'closed'; }

    public static function getStatuses(): array
    {
        return ['open' => 'Open', 'closed' => 'Closed'];
    }
}
