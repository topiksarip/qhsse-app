<?php

namespace App\Policies\Modules\Environment;

use App\Models\Modules\Environment\EnvironmentalRecord;
use App\Models\User;

class EnvironmentalRecordPolicy
{
    /**
     * Determine whether the user can view any environmental records.
     */
    public function viewAny(User $user): bool
    {
        return $user->can('environment.records.view');
    }

    /**
     * Determine whether the user can view the environmental record.
     */
    public function view(User $user, EnvironmentalRecord $environmentalRecord): bool
    {
        if (! $user->can('environment.records.view')) {
            return false;
        }

        // Organization scope check
        if ($user->hasRole('Super Admin')) {
            return true;
        }

        if ($user->hasAnyRole(['QHSSE Manager', 'Admin'])) {
            return true;
        }

        if ($user->hasRole('QHSSE Officer') && $user->employee?->site_id === $environmentalRecord->site_id) {
            return true;
        }

        if ($user->hasAnyRole(['Supervisor', 'Department Head']) && $user->employee?->site_id === $environmentalRecord->site_id) {
            return true;
        }

        if ($environmentalRecord->reporter_id === $user->id) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can create environmental records.
     */
    public function create(User $user): bool
    {
        return $user->can('environment.records.create');
    }

    /**
     * Determine whether the user can update the environmental record.
     */
    public function update(User $user, EnvironmentalRecord $environmentalRecord): bool
    {
        if (! $user->can('environment.records.update')) {
            return false;
        }

        // Reporter can edit their own record if not closed
        if ($environmentalRecord->reporter_id === $user->id && $environmentalRecord->status !== 'closed') {
            return true;
        }

        // QHSSE can edit any record if not closed
        if ($user->hasAnyRole(['Super Admin', 'Admin', 'QHSSE Manager', 'QHSSE Officer']) && $environmentalRecord->status !== 'closed') {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can delete the environmental record.
     */
    public function delete(User $user, EnvironmentalRecord $environmentalRecord): bool
    {
        // Environmental records are never deleted, only closed
        return false;
    }

    /**
     * Determine whether the user can export environmental records.
     */
    public function export(User $user): bool
    {
        return $user->can('environment.records.export');
    }

    /**
     * Determine whether the user can start investigation / open CAPA on the record.
     * Only QHSSE roles may transition recorded/investigated records.
     */
    public function investigate(User $user, EnvironmentalRecord $environmentalRecord): bool
    {
        if (! $user->can('environment.records.approve')) {
            return false;
        }

        if ($user->hasAnyRole(['Super Admin', 'Admin', 'QHSSE Manager', 'QHSSE Officer'])) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can close the record (requires reason).
     */
    public function close(User $user, EnvironmentalRecord $environmentalRecord): bool
    {
        if (! $user->can('environment.records.close')) {
            return false;
        }

        if ($user->hasAnyRole(['Super Admin', 'Admin', 'QHSSE Manager', 'QHSSE Officer'])) {
            return true;
        }

        return false;
    }

    /**
     * Available transitions for the current status (for UI + guard reference).
     */
    public static function getAvailableTransitions(string $status): array
    {
        return match ($status) {
            'recorded' => [
                ['action' => 'investigate', 'label' => 'Mulai Investigasi', 'permission' => 'environment.records.approve', 'requires_reason' => false],
                ['action' => 'close', 'label' => 'Tutup Langsung', 'permission' => 'environment.records.close', 'requires_reason' => true],
            ],
            'investigated' => [
                ['action' => 'open_action', 'label' => 'Buka CAPA', 'permission' => 'environment.records.approve', 'requires_reason' => false],
                ['action' => 'close', 'label' => 'Tutup', 'permission' => 'environment.records.close', 'requires_reason' => true],
            ],
            'action_open' => [
                ['action' => 'close', 'label' => 'Tutup', 'permission' => 'environment.records.close', 'requires_reason' => true],
            ],
            'closed' => [],
            default => [],
        };
    }
}
