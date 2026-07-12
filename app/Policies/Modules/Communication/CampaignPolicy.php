<?php

namespace App\Policies\Modules\Communication;

use App\Models\Modules\Communication\Campaign;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class CampaignPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('communication.campaigns.view');
    }

    public function view(User $user, Campaign $campaign): bool
    {
        if (!$user->hasPermissionTo('communication.campaigns.view')) {
            return false;
        }

        // Super Admin and Admin can view all
        if ($user->hasRole(['Super Admin', 'Admin'])) {
            return true;
        }

        // Author can always view their own campaign
        if ($campaign->author_id === $user->id) {
            return true;
        }

        // QHSSE Manager, Auditor, Top Management: view all (scope: all)
        if ($user->hasRole(['QHSSE Manager', 'Auditor', 'Top Management'])) {
            return true;
        }

        // QHSSE Officer: view campaigns for their site
        if ($user->hasRole('QHSSE Officer')) {
            if ($campaign->target_audience === 'all') {
                return true;
            }
            if ($campaign->target_audience === 'specific_site' && $campaign->site_id === $user->employee?->site_id) {
                return true;
            }
        }

        // Other roles: view campaigns targeted to them
        return $this->isInTargetAudience($user, $campaign);
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo('communication.campaigns.create');
    }

    public function update(User $user, Campaign $campaign): bool
    {
        if (!$user->hasPermissionTo('communication.campaigns.update')) {
            return false;
        }

        // Only draft campaigns can be updated
        if ($campaign->status !== 'draft') {
            // Super Admin can update expires_at even for published campaigns
            return $user->hasRole('Super Admin');
        }

        return true;
    }

    public function delete(User $user, Campaign $campaign): bool
    {
        // Only Super Admin and Admin can delete
        if (!$user->hasRole(['Super Admin', 'Admin'])) {
            return false;
        }

        // Cannot delete published campaigns (data integrity)
        return $campaign->status === 'draft';
    }

    public function publish(User $user, Campaign $campaign): bool
    {
        if (!$user->hasPermissionTo('communication.campaigns.publish')) {
            return false;
        }

        // Can only publish draft campaigns
        return $campaign->status === 'draft';
    }

    public function export(User $user): bool
    {
        return $user->hasPermissionTo('communication.campaigns.export');
    }

    public function viewAcknowledgments(User $user): bool
    {
        return $user->hasPermissionTo('communication.acknowledgments.view');
    }

    public function acknowledge(User $user, Campaign $campaign): bool
    {
        // Campaign must be published
        if ($campaign->status !== 'published') {
            return false;
        }

        // User must be in target audience
        if (!$this->isInTargetAudience($user, $campaign)) {
            return false;
        }

        // Cannot acknowledge twice
        if ($campaign->isAcknowledgedBy($user)) {
            return false;
        }

        return true;
    }

    protected function isInTargetAudience(User $user, Campaign $campaign): bool
    {
        return match ($campaign->target_audience) {
            'all' => true,
            'specific_site' => $user->employee?->site_id === $campaign->site_id,
            'specific_department' => $user->employee?->department_id === $campaign->department_id,
            'specific_role' => $user->hasRole($campaign->target_role),
            default => false,
        };
    }
}
