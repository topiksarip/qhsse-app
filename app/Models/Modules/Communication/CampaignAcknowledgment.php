<?php

namespace App\Models\Modules\Communication;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CampaignAcknowledgment extends Model
{
    use HasFactory;

    protected $fillable = [
        'campaign_id',
        'user_id',
        'acknowledged_at',
        'ip_address',
    ];

    protected $casts = [
        'acknowledged_at' => 'datetime',
    ];

    // Relationships
    public function campaign(): BelongsTo
    {
        return $this->belongsTo(Campaign::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // Scopes
    public function scopeForCampaign($query, int $campaignId)
    {
        return $query->where('campaign_id', $campaignId);
    }

    public function scopeForUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeRecent($query, int $days = 30)
    {
        return $query->where('acknowledged_at', '>=', now()->subDays($days));
    }

    // Helper methods
    public static function acknowledge(Campaign $campaign, User $user, ?string $ipAddress = null): self
    {
        return self::create([
            'campaign_id' => $campaign->id,
            'user_id' => $user->id,
            'acknowledged_at' => now(),
            'ip_address' => $ipAddress ?? request()->ip(),
        ]);
    }

    public static function hasAcknowledged(Campaign $campaign, User $user): bool
    {
        return self::where('campaign_id', $campaign->id)
            ->where('user_id', $user->id)
            ->exists();
    }
}
