<?php

namespace App\Http\Controllers\Core;

use App\Core\Export\CsvExporter;
use App\Core\Query\ListQuery;
use App\Http\Controllers\Controller;
use App\Http\Requests\Core\SiteRequest;
use App\Models\Core\MasterData\Site;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class SiteController extends Controller
{
    public function index(ListQuery $listQuery): Response
    {
        $items = $listQuery->paginate(
            Site::query(),
            ['code', 'name', 'address'],
        );

        return Inertia::render('Core/Sites/Index', [
            'items' => $items,
            'filters' => $listQuery->filters(),
        ]);
    }

    public function export(ListQuery $listQuery, CsvExporter $exporter): StreamedResponse
    {
        $query = $listQuery->apply(
            Site::query(),
            ['code', 'name', 'address'],
        );

        return $exporter->stream($query, [
            'Code' => 'code',
            'Name' => 'name',
            'Address' => 'address',
            'Active' => fn (Site $site): string => $site->is_active ? 'Yes' : 'No',
        ], 'sites-export.csv');
    }

    public function create(): Response
    {
        return Inertia::render('Core/Sites/Form', ['item' => null]);
    }

    public function store(SiteRequest $request): RedirectResponse
    {
        Site::create($request->validated() + ['is_active' => $request->boolean('is_active', true)]);

        return redirect()->route('core.sites.index');
    }

    public function edit(Site $site): Response
    {
        return Inertia::render('Core/Sites/Form', ['item' => $site]);
    }

    public function update(SiteRequest $request, Site $site): RedirectResponse
    {
        $site->update($request->validated() + ['is_active' => $request->boolean('is_active')]);

        return redirect()->route('core.sites.index');
    }

    public function destroy(Site $site): RedirectResponse
    {
        $site->update(['is_active' => false]);

        return redirect()->route('core.sites.index');
    }
}
