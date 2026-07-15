<?php

namespace App\Http\Controllers\Core;

use App\Core\Audit\AuditService;
use App\Http\Controllers\Controller;
use App\Http\Requests\Core\UserAdminRequest;
use App\Models\Core\MasterData\Company;
use App\Models\Core\Users\Employee;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\Permission\Models\Role;

class UserAdminController extends Controller
{
    public function __construct(
        protected AuditService $auditService,
    ) {}

    public function index(): Response
    {
        $users = User::query()
            ->with(['company:id,name', 'employee:id,name,employee_no', 'roles:id,name'])
            ->when(request('search'), function ($query, string $search): void {
                $query->where(function ($query) use ($search): void {
                    $query->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                });
            })
            ->orderBy('name')
            ->paginate(10)
            ->withQueryString();

        return Inertia::render('Core/Users/Index', [
            'users' => $users,
            'filters' => request()->only('search'),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Core/Users/Form', [
            'userRecord' => null,
            'companies' => Company::query()->where('is_active', true)->orderBy('name')->get(['id', 'name']),
            'employees' => Employee::query()->where('is_active', true)->orderBy('name')->get(['id', 'name', 'employee_no', 'company_id']),
            'roles' => Role::query()->orderBy('name')->get(['id', 'name']),
            'assignedRoles' => [],
        ]);
    }

    public function store(UserAdminRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $roles = $data['roles'] ?? [];

        $user = User::create(Arr::except($data, 'roles') + ['is_active' => $request->boolean('is_active', true)]);
        $user->syncRoles($roles);

        $this->auditService->created($user, $request->user(), 'core', $user->id);

        return redirect()->route('core.users.index');
    }

    public function edit(User $user): Response
    {
        return Inertia::render('Core/Users/Form', [
            'userRecord' => $user,
            'companies' => Company::query()->where('is_active', true)->orderBy('name')->get(['id', 'name']),
            'employees' => Employee::query()->where('is_active', true)->orderBy('name')->get(['id', 'name', 'employee_no', 'company_id']),
            'roles' => Role::query()->orderBy('name')->get(['id', 'name']),
            'assignedRoles' => $user->roles()->pluck('name'),
        ]);
    }

    public function update(UserAdminRequest $request, User $user): RedirectResponse
    {
        $data = $request->validated() + ['is_active' => $request->boolean('is_active')];
        $roles = $data['roles'] ?? [];
        $data = Arr::except($data, 'roles');

        if (blank($data['password'] ?? null)) {
            $data = Arr::except($data, 'password');
        }

        // M20 WS-1: prevent self-lock
        if ($user->id === Auth::id() && ! $request->boolean('is_active')) {
            return back()->with('error', 'Anda tidak dapat menonaktifkan akun Anda sendiri.');
        }

        // M20 WS-1: prevent deactivating the last active Super Admin
        if (! $request->boolean('is_active') && $user->hasRole('Super Admin')) {
            $activeSuperAdmins = User::role('Super Admin')->where('is_active', true)->count();
            if ($activeSuperAdmins <= 1) {
                return back()->with('error', 'Tidak dapat menonaktifkan Super Admin terakhir yang aktif.');
            }
        }

        $old = $user->getOriginal();
        $user->update($data);
        $user->syncRoles($roles);

        $this->auditService->updated($user, $old, $request->user(), 'core', $user->id);

        return redirect()->route('core.users.index');
    }

    public function destroy(User $user): RedirectResponse
    {
        // M20 WS-1: prevent self-lock
        if ($user->id === Auth::id()) {
            return back()->with('error', 'Anda tidak dapat mengunci akun Anda sendiri.');
        }

        // M20 WS-1: prevent removing the last active Super Admin
        if ($user->hasRole('Super Admin')) {
            $activeSuperAdmins = User::role('Super Admin')->where('is_active', true)->count();
            if ($activeSuperAdmins <= 1) {
                return back()->with('error', 'Tidak dapat mengunci Super Admin terakhir yang aktif.');
            }
        }

        $old = $user->getOriginal();
        $user->update(['is_active' => false]);

        $this->auditService->updated($user, $old, Auth::user(), 'core', $user->id);

        return redirect()->route('core.users.index');
    }
}
