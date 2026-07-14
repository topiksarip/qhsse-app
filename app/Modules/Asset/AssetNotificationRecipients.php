<?php

namespace App\Modules\Asset;

use App\Models\Modules\Asset\Asset;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class AssetNotificationRecipients
{
    /** @return Collection<int, User> */
    public function forCertificate(Asset $asset, string $status): Collection
    {
        return User::query()
            ->where('is_active', true)
            ->where(function (Builder $query) use ($asset, $status): void {
                $query->whereHas('roles', fn (Builder $roles) => $roles->where('name', 'QHSSE Manager'))
                    ->orWhere(function (Builder $user) use ($asset): void {
                        $user->whereHas('roles', fn (Builder $roles) => $roles->where('name', 'QHSSE Officer'))
                            ->whereHas('employee', fn (Builder $employee) => $employee->where('site_id', $asset->site_id));
                    });

                if ($asset->department_id !== null) {
                    $query->orWhere(function (Builder $user) use ($asset): void {
                        $user->whereHas('roles', fn (Builder $roles) => $roles->where('name', 'Department Head'))
                            ->whereHas('employee', fn (Builder $employee) => $employee->where('department_id', $asset->department_id));
                    });
                }

                if ($asset->safety_critical && in_array($status, ['expiring_critical', 'expired'], true)) {
                    $query->orWhereHas('roles', fn (Builder $roles) => $roles->where('name', 'Top Management'));
                }
            })
            ->get()
            ->unique('id')
            ->values();
    }

    /** @return Collection<int, User> */
    public function forInspection(Asset $asset): Collection
    {
        return User::query()
            ->where('is_active', true)
            ->where(function (Builder $query) use ($asset): void {
                $query->where(function (Builder $user) use ($asset): void {
                    $user->whereHas('roles', fn (Builder $roles) => $roles->where('name', 'QHSSE Officer'))
                        ->whereHas('employee', fn (Builder $employee) => $employee->where('site_id', $asset->site_id));
                });

                if ($asset->department_id !== null) {
                    $query->orWhere(function (Builder $user) use ($asset): void {
                        $user->whereHas('roles', fn (Builder $roles) => $roles->where('name', 'Supervisor'))
                            ->whereHas('employee', fn (Builder $employee) => $employee->where('department_id', $asset->department_id));
                    });
                }

                if ($asset->safety_critical) {
                    $query->orWhereHas('roles', fn (Builder $roles) => $roles->where('name', 'QHSSE Manager'));
                }
            })
            ->get()
            ->unique('id')
            ->values();
    }
}
