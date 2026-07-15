<?php

namespace App\Policies\Modules\DocumentControl;

use App\Models\Modules\DocumentControl\ControlledDocument;
use App\Models\User;

class ControlledDocumentPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('document.control.view');
    }

    public function view(User $user, ControlledDocument $controlledDocument): bool
    {
        return $user->can('document.control.view');
    }

    public function create(User $user): bool
    {
        return $user->can('document.control.create');
    }

    public function update(User $user, ControlledDocument $controlledDocument): bool
    {
        return $user->can('document.control.update');
    }

    public function delete(User $user, ControlledDocument $controlledDocument): bool
    {
        return $user->can('document.control.delete');
    }

    public function export(User $user): bool
    {
        return $user->can('document.control.export');
    }
}
