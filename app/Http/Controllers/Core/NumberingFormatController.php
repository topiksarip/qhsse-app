<?php

namespace App\Http\Controllers\Core;

use App\Core\Numbering\NumberingService;
use App\Http\Controllers\Controller;
use App\Http\Requests\Core\GenerateNumberRequest;
use App\Http\Requests\Core\NumberingFormatRequest;
use App\Models\Core\Numbering\GeneratedNumber;
use App\Models\Core\Numbering\NumberingFormat;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class NumberingFormatController extends Controller
{
    public function index(Request $request): Response
    {
        $formats = NumberingFormat::query()
            ->when($request->string('search')->toString(), function ($query, string $search): void {
                $query->where(function ($query) use ($search): void {
                    $query->where('module_name', 'like', "%{$search}%")
                        ->orWhere('prefix', 'like', "%{$search}%")
                        ->orWhere('sample', 'like', "%{$search}%");
                });
            })
            ->orderBy('module_name')
            ->paginate(10)
            ->withQueryString();

        $recentNumbers = GeneratedNumber::query()
            ->latest()
            ->limit(10)
            ->get(['id', 'module_name', 'number', 'site_code', 'year', 'sequence', 'created_at']);

        return Inertia::render('Core/Numbering/Index', [
            'formats' => $formats,
            'recentNumbers' => $recentNumbers,
            'filters' => $request->only('search'),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Core/Numbering/Form', ['format' => null]);
    }

    public function store(NumberingFormatRequest $request, NumberingService $service): RedirectResponse
    {
        $format = NumberingFormat::create($this->payload($request));
        $format->update(['sample' => $service->sample($format)]);

        return redirect()->route('core.numbering.index');
    }

    public function edit(NumberingFormat $numbering_format): Response
    {
        return Inertia::render('Core/Numbering/Form', ['format' => $numbering_format]);
    }

    public function update(NumberingFormatRequest $request, NumberingFormat $numbering_format, NumberingService $service): RedirectResponse
    {
        $numbering_format->update($this->payload($request));
        $numbering_format->update(['sample' => $service->sample($numbering_format)]);

        return redirect()->route('core.numbering.index');
    }

    public function generate(GenerateNumberRequest $request, NumberingService $service): RedirectResponse
    {
        $generated = $service->generate(
            $request->string('module_name')->toString(),
            $request->user(),
            $request->string('site_code')->toString() ?: null,
            $request->string('reference_type')->toString() ?: null,
            $request->integer('reference_id') ?: null,
            $request->validated('metadata') ?? [],
        );

        return redirect()->route('core.numbering.index')->with('generated_number', $generated->number);
    }

    private function payload(NumberingFormatRequest $request): array
    {
        return $request->safe()->except(['include_year', 'include_site_code', 'is_active']) + [
            'include_year' => $request->boolean('include_year'),
            'include_site_code' => $request->boolean('include_site_code'),
            'is_active' => $request->boolean('is_active', true),
        ];
    }
}
