<?php

namespace App\Http\Controllers\Core;

use App\Http\Controllers\Controller;
use App\Http\Requests\Core\UserAdminRequest;
use App\Models\Core\MasterData\Company;
use App\Models\Core\Users\Employee;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Arr;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\Permission\Models\Role;

class UserAdminController extends Controller
{
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

        $user->update($data);
        $user->syncRoles($roles);

        return redirect()->route('core.users.index');
    }

    public function destroy(User $user): RedirectResponse
    {
        $user->update(['is_active' => false]);

        return redirect()->route('core.users.index');
    }
}
