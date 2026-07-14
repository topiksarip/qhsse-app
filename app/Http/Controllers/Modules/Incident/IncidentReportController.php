<?php

namespace App\Http\Controllers\Modules\Incident;

use App\Http\Controllers\Controller;
use App\Http\Requests\Modules\Incident\IncidentReportRequest;
use App\Models\Modules\Incident\IncidentReport;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class IncidentReportController extends Controller
{
    public function index(Request $request): Response
    {
        $items = IncidentReport::query()
            ->with(['site', 'department', 'severity', 'reporter', 'assignee'])
            ->when($request->search, fn ($q, $s) => $q->where('title', 'like', "%{$s}%")->orWhere('number', 'like', "%{$s}%"))
            ->when($request->status, fn ($q, $s) => $q->where('status', $s))
            ->orderByDesc('id')
            ->paginate(10)
            ->withQueryString();

        return Inertia::render('Modules/Incident/Index', [
            'items' => $items,
            'filters' => $request->only(['search', 'status']),
            'statuses' => IncidentReport::STATUSES,
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Modules/Incident/Form', ['item' => null]);
    }

    public function store(IncidentReportRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $data['reporter_id'] = $data['reporter_id'] ?? Auth::id();
        $data['created_by'] = Auth::id();
        $data['updated_by'] = Auth::id();
        try {
            $data['number'] = app(\App\Core\Numbering\NumberingService::class)
                ->generate('incident-reporting', Auth::user())->number;
        } catch (\Throwable $e) {
            $data['number'] = 'IR-' . now()->format('Ymd') . '-' . str_pad((string) random_int(1, 9999), 4, '0', STR_PAD_LEFT);
        }
        IncidentReport::create($data);

        return redirect()->route('modules.incident.index');
    }

    public function show(IncidentReport $incidentReport): Response
    {
        $incidentReport->load(['site', 'area', 'department', 'company', 'category', 'severity', 'priority', 'reporter', 'assignee', 'reviewer', 'approver', 'verifier']);
        return Inertia::render('Modules/Incident/Show', ['item' => $incidentReport]);
    }

    public function edit(IncidentReport $incidentReport): Response
    {
        return Inertia::render('Modules/Incident/Form', ['item' => $incidentReport]);
    }

    public function update(IncidentReportRequest $request, IncidentReport $incidentReport): RedirectResponse
    {
        $data = $request->validated();
        $data['updated_by'] = Auth::id();
        $incidentReport->update($data);

        return redirect()->route('modules.incident.index');
    }

    public function destroy(IncidentReport $incidentReport): RedirectResponse
    {
        $incidentReport->delete();
        return redirect()->route('modules.incident.index');
    }

    public function submit(Request $request, IncidentReport $incidentReport): RedirectResponse
    {
        abort_unless(in_array($incidentReport->status, ['draft', 'rejected', 'cancelled']), 422);
        $incidentReport->update(['status' => 'submitted', 'updated_by' => Auth::id()]);
        return back();
    }

    public function review(Request $request, IncidentReport $incidentReport): RedirectResponse
    {
        abort_unless($incidentReport->status === 'submitted', 422);
        $incidentReport->update(['status' => 'under_review', 'reviewer_id' => Auth::id(), 'updated_by' => Auth::id()]);
        return back();
    }

    public function approve(Request $request, IncidentReport $incidentReport): RedirectResponse
    {
        abort_unless($incidentReport->status === 'under_review', 422);
        $incidentReport->update(['status' => 'approved', 'approver_id' => Auth::id(), 'updated_by' => Auth::id()]);
        return back();
    }

    public function reject(Request $request, IncidentReport $incidentReport): RedirectResponse
    {
        abort_unless(in_array($incidentReport->status, ['submitted', 'under_review']), 422);
        $request->validate(['reason' => ['required', 'string', 'max:1000']]);
        $incidentReport->update(['status' => 'rejected', 'updated_by' => Auth::id(), 'meta' => array_merge((array) $incidentReport->meta, ['reject_reason' => $request->reason])]);
        return back();
    }

    public function verify(Request $request, IncidentReport $incidentReport): RedirectResponse
    {
        abort_unless($incidentReport->status === 'approved', 422);
        $incidentReport->update(['status' => 'waiting_verification', 'verifier_id' => Auth::id(), 'updated_by' => Auth::id()]);
        return back();
    }

    public function close(Request $request, IncidentReport $incidentReport): RedirectResponse
    {
        abort_unless(in_array($incidentReport->status, ['waiting_verification', 'in_progress']), 422);
        $incidentReport->update(['status' => 'closed', 'updated_by' => Auth::id()]);
        return back();
    }

    public function reopen(Request $request, IncidentReport $incidentReport): RedirectResponse
    {
        abort_unless($incidentReport->status === 'closed', 422);
        $incidentReport->update(['status' => 'draft', 'updated_by' => Auth::id()]);
        return back();
    }
}
