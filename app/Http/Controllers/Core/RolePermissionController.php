<?php

namespace App\Http\Controllers\Core;

use App\Core\Audit\AuditService;
use App\Http\Controllers\Controller;
use App\Http\Requests\Core\UpdateRolePermissionsRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolePermissionController extends Controller
{
    private const PROTECTED_PERMISSIONS = ['core.roles.manage'];

    public function __construct(private readonly AuditService $audit) {}

    public function index(Request $request): Response
    {
        $roles = Role::query()
            ->withCount(['permissions', 'users'])
            ->orderBy('name')
            ->get(['id', 'name', 'guard_name']);
        $selected = $roles->firstWhere('id', $request->integer('role')) ?? $roles->first();
        $selectedRole = $selected ? Role::query()->with('permissions:id,name')->findOrFail($selected->id) : null;

        $permissions = Permission::query()
            ->whereNotIn('name', self::PROTECTED_PERMISSIONS)
            ->orderBy('name')
            ->get(['id', 'name'])
            ->groupBy(fn (Permission $permission): string => $this->groupKey($permission->name))
            ->map(fn ($items, string $key): array => [
                'key' => $key,
                'label' => $this->groupLabel($key),
                'permissions' => $items->values(),
            ])->values();

        return Inertia::render('Core/Roles/Index', [
            'roles' => $roles,
            'selectedRole' => $selectedRole ? [
                'id' => $selectedRole->id,
                'name' => $selectedRole->name,
                'permissions' => $selectedRole->permissions
                    ->whereNotIn('name', self::PROTECTED_PERMISSIONS)
                    ->pluck('name')->values(),
                'immutable' => $selectedRole->name === 'Super Admin',
            ] : null,
            'permissionGroups' => $permissions,
        ]);
    }

    public function update(UpdateRolePermissionsRequest $request, Role $role): RedirectResponse
    {
        $old = $role->permissions()->pluck('name')->sort()->values()->all();
        $protected = array_values(array_intersect($old, self::PROTECTED_PERMISSIONS));
        $requested = $request->validated('permissions', []);
        $new = collect([...$requested, ...$protected])->unique()->sort()->values()->all();

        DB::transaction(function () use ($request, $role, $old, $new): void {
            $role->syncPermissions($new);
            $this->audit->log(
                'role_permissions_updated',
                $role,
                ['permissions' => $old],
                ['permissions' => $new],
                $request->user(),
                'core_roles',
                $role->id,
            );
        });
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        return redirect()->route('core.roles.index', ['role' => $role->id])
            ->with('success', "Permission role {$role->name} berhasil diperbarui.");
    }

    private function groupKey(string $permission): string
    {
        $parts = explode('.', $permission);

        return implode('.', array_slice($parts, 0, min(2, count($parts))));
    }

    private function groupLabel(string $key): string
    {
        return str($key)->replace('.', ' / ')->headline()->toString();
    }
}
