<?php

namespace App\Http\Controllers\Modules\Security;

use App\Core\Activity\ActivityService;
use App\Core\Audit\AuditService;
use App\Core\Export\CsvExporter;
use App\Core\Numbering\NumberingService;
use App\Core\Query\ListQuery;
use App\Http\Controllers\Controller;
use App\Http\Requests\Modules\Security\StoreSecurityIncidentRequest;
use App\Http\Requests\Modules\Security\UpdateSecurityIncidentRequest;
use App\Models\Core\MasterData\Area;
use App\Models\Core\MasterData\Severity;
use App\Models\Core\MasterData\Site;
use App\Models\Modules\Security\SecurityIncident;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

class SecurityIncidentController extends Controller
{
    public function __construct(
        protected NumberingService $numberingService,
        protected AuditService $auditService,
        protected ActivityService $activityService,
    ) {
        $this->authorizeResource(SecurityIncident::class, 'security_incident');
    }

    public function index(Request $request): InertiaResponse
    {
        $query = SecurityIncident::query()
            ->with(['site', 'area', 'reporter', 'severity'])
            ->select('security_incidents.*');

        // Organization scope
        $scope = $request->input('scope', 'all');
        $user = $request->user();

        if ($scope === 'site' && $user->employee?->site_id) {
            $query->where('security_incidents.site_id', $user->employee->site_id);
        } elseif ($scope === 'own') {
            $query->where('security_incidents.reported_by', $user->id);
        }

        // Filters
        if ($request->filled('site_id')) {
            $query->where('security_incidents.site_id', $request->input('site_id'));
        }

        if ($request->filled('type')) {
            $query->where('security_incidents.type', $request->input('type'));
        }

        if ($request->filled('status')) {
            $query->where('security_incidents.status', $request->input('status'));
        }

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('security_incidents.security_number', 'like', "%{$search}%")
                    ->orWhere('security_incidents.title', 'like', "%{$search}%");
            });
        }

        $incidents = ListQuery::for($query)
            ->defaultSort('-created_at')
            ->paginate($request->input('per_page', 15))
            ->withQueryString();

        return Inertia::render('Modules/Security/Incidents/Index', [
            'incidents' => $incidents,
            'filters' => $request->only(['scope', 'site_id', 'type', 'status', 'search']),
            'sites' => Site::select('id', 'name')->orderBy('name')->get(),
            'types' => SecurityIncident::getTypes(),
            'statuses' => SecurityIncident::getStatuses(),
        ]);
    }

    public function create(): InertiaResponse
    {
        return Inertia::render('Modules/Security/Incidents/Form', [
            'sites' => Site::select('id', 'name')->orderBy('name')->get(),
            'areas' => Area::select('id', 'site_id', 'name')->orderBy('name')->get(),
            'severities' => Severity::select('id', 'name', 'level')->orderBy('level')->get(),
            'types' => SecurityIncident::getTypes(),
        ]);
    }

    public function store(StoreSecurityIncidentRequest $request)
    {
        return DB::transaction(function () use ($request) {
            $user = $request->user();

            $securityNumber = $this->numberingService->generate(
                key: 'security',
                siteId: null,
                includeSiteCode: false
            );

            $data = $request->validated();
            $data['security_number'] = $securityNumber;
            $data['reported_by'] = $user->id;

            $incident = SecurityIncident::create($data);

            $this->auditService->log(
                moduleName: 'security',
                action: 'create',
                referenceId: $incident->id,
                details: "Security incident {$incident->security_number} created",
                userId: $user->id
            );

            $this->activityService->log(
                moduleName: 'security',
                referenceId: $incident->id,
                action: 'create',
                description: "Security incident {$incident->security_number} reported by {$user->name}",
                userId: $user->id
            );

            return redirect()->route('security.incidents.show', $incident)
                ->with('success', "Security incident {$incident->security_number} created successfully");
        });
    }

    public function show(SecurityIncident $securityIncident): InertiaResponse
    {
        $securityIncident->load(['site', 'area', 'reporter.employee', 'severity']);

        return Inertia::render('Modules/Security/Incidents/Show', [
            'incident' => $securityIncident,
        ]);
    }

    public function edit(SecurityIncident $securityIncident): InertiaResponse
    {
        return Inertia::render('Modules/Security/Incidents/Form', [
            'incident' => $securityIncident,
            'sites' => Site::select('id', 'name')->orderBy('name')->get(),
            'areas' => Area::select('id', 'site_id', 'name')->orderBy('name')->get(),
            'severities' => Severity::select('id', 'name', 'level')->orderBy('level')->get(),
            'types' => SecurityIncident::getTypes(),
            'statuses' => SecurityIncident::getStatuses(),
        ]);
    }

    public function update(UpdateSecurityIncidentRequest $request, SecurityIncident $securityIncident)
    {
        return DB::transaction(function () use ($request, $securityIncident) {
            $user = $request->user();

            if ($request->input('status') === 'closed' && $securityIncident->status !== 'closed') {
                $securityIncident->resolved_at = now();
            }

            $securityIncident->update($request->validated());

            $this->auditService->log(
                moduleName: 'security',
                action: 'update',
                referenceId: $securityIncident->id,
                details: "Security incident {$securityIncident->security_number} updated",
                userId: $user->id
            );

            return redirect()->route('security.incidents.show', $securityIncident)
                ->with('success', 'Security incident updated successfully');
        });
    }

    public function export(Request $request)
    {
        $this->authorize('export', SecurityIncident::class);

        $query = SecurityIncident::query()->with(['site', 'reporter', 'severity']);

        $scope = $request->input('scope', 'all');
        $user = $request->user();

        if ($scope === 'site' && $user->employee?->site_id) {
            $query->where('security_incidents.site_id', $user->employee->site_id);
        } elseif ($scope === 'own') {
            $query->where('security_incidents.reported_by', $user->id);
        }

        if ($request->filled('site_id')) {
            $query->where('security_incidents.site_id', $request->input('site_id'));
        }

        $incidents = $query->orderBy('created_at', 'desc')->get();

        return CsvExporter::export(
            data: $incidents,
            filename: 'security_incidents_'.now()->format('Y-m-d_His').'.csv',
            columns: [
                'security_number' => 'Security Number',
                'type' => 'Type',
                'title' => 'Title',
                'site.name' => 'Site',
                'occurred_at' => 'Occurred At',
                'severity.name' => 'Severity',
                'status' => 'Status',
                'reporter.name' => 'Reporter',
                'created_at' => 'Created At',
            ]
        );
    }
}
