<?php

namespace Database\Seeders;

use App\Core\Permissions\CorePermissions;
use App\Core\Permissions\ModulePermissions;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolesAndPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach (array_merge(CorePermissions::all(), ModulePermissions::all()) as $permission) {
            Permission::findOrCreate($permission);
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach (CorePermissions::roleMap() as $roleName => $permissions) {
            $permissions = array_merge($permissions, $this->modulePermissionsFor($roleName));
            Role::findOrCreate($roleName)->syncPermissions($permissions);
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    /**
     * Grant module permissions based on role responsibilities.
     */
    private function modulePermissionsFor(string $roleName): array
    {
        $mods = ModulePermissions::modules();
        $actionsFull = ModulePermissions::actions();
        $actionsReporter = ['view', 'view.all', 'create', 'update', 'submit', 'reopen'];

        return match ($roleName) {
            'Super Admin', 'Admin' => array_map(
                fn ($m) => array_map(fn ($a) => "{$m}.{$a}", $actionsFull),
                $mods
            ) ? array_merge(...array_map(fn ($m) => array_map(fn ($a) => "{$m}.{$a}", $actionsFull), $mods)) : [],
            'QHSSE Manager', 'QHSSE Officer', 'Auditor', 'Top Management' => array_merge(
                ...array_map(fn ($m) => array_map(fn ($a) => "{$m}.{$a}", $actionsFull), $mods)
            ),
            'Supervisor', 'Department Head' => array_merge(
                ...array_map(fn ($m) => array_map(fn ($a) => "{$m}.{$a}", $actionsReporter), $mods)
            ),
            'Employee / Reporter', 'Contractor' => array_merge(
                ...array_map(fn ($m) => array_map(fn ($a) => "{$m}.{$a}", $actionsReporter), $mods)
            ),
            default => [],
        };
    }
}
