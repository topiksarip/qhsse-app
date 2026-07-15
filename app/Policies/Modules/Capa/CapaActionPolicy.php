<?php
namespace App\Policies\Modules\Capa;
use App\Models\Modules\Capa\CapaAction; use App\Models\User;
class CapaActionPolicy {
    public function viewAny(User $user): bool { return $user->can('capa.actions.view'); }
    public function view(User $user, CapaAction $capaAction): bool { return $user->can('capa.actions.view'); }
    public function create(User $user): bool { return $user->can('capa.actions.create'); }
    public function update(User $user, CapaAction $capaAction): bool { return $user->can('capa.actions.update'); }
    public function delete(User $user, CapaAction $capaAction): bool { return $user->can('capa.actions.delete'); }
    public function export(User $user): bool { return $user->can('capa.actions.export'); }
}
