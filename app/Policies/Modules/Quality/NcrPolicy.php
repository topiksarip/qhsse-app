<?php
namespace App\Policies\Modules\Quality;
use App\Models\Modules\Quality\Ncr; use App\Models\User;
class NcrPolicy {
    public function viewAny(User $user): bool { return $user->can('quality.ncrs.view'); }
    public function view(User $user, Ncr $ncr): bool {
        if(!$user->can('quality.ncrs.view')) return false;
        if($user->hasRole('Super Admin')) return true;
        if($user->hasAnyRole(['QHSSE Manager','Admin'])) return true;
        if($user->hasRole('QHSSE Officer') && $user->employee?->site_id===$ncr->site_id) return true;
        return false;
    }
    public function create(User $user): bool { return $user->can('quality.ncrs.create'); }
    public function update(User $user, Ncr $ncr): bool {
        if(!$user->can('quality.ncrs.update')) return false;
        if($ncr->isClosed()) return false;
        if($user->hasAnyRole(['Super Admin','Admin','QHSSE Manager'])) return true;
        if($user->hasRole('QHSSE Officer') && $user->employee?->site_id===$ncr->site_id) return true;
        return false;
    }
    public function delete(User $user, Ncr $ncr): bool { return $user->can('quality.ncrs.delete'); }
    public function export(User $user): bool { return $user->can('quality.ncrs.export'); }
}
