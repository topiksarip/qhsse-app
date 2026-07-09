<?php

namespace App\Http\Controllers\Core;

use App\Http\Controllers\Controller;
use App\Http\Requests\Core\CompanyRequest;
use App\Models\Core\MasterData\Company;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class CompanyController extends Controller
{
    public function index(): Response
    {
        $companies = Company::query()
            ->when(request('search'), function ($query, string $search): void {
                $query->where(function ($query) use ($search): void {
                    $query->where('code', 'like', "%{$search}%")
                        ->orWhere('name', 'like', "%{$search}%")
                        ->orWhere('type', 'like', "%{$search}%");
                });
            })
            ->orderBy('name')
            ->paginate(10)
            ->withQueryString();

        return Inertia::render('Core/Companies/Index', [
            'companies' => $companies,
            'filters' => request()->only('search'),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Core/Companies/Form', [
            'company' => null,
        ]);
    }

    public function store(CompanyRequest $request): RedirectResponse
    {
        Company::create($request->validated() + ['is_active' => $request->boolean('is_active', true)]);

        return redirect()->route('core.companies.index');
    }

    public function edit(Company $company): Response
    {
        return Inertia::render('Core/Companies/Form', [
            'company' => $company,
        ]);
    }

    public function update(CompanyRequest $request, Company $company): RedirectResponse
    {
        $company->update($request->validated() + ['is_active' => $request->boolean('is_active')]);

        return redirect()->route('core.companies.index');
    }

    public function destroy(Company $company): RedirectResponse
    {
        $company->update(['is_active' => false]);

        return redirect()->route('core.companies.index');
    }
}
