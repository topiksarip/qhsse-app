<?php

namespace App\Http\Controllers\Modules\Security;

use App\Core\Activity\ActivityService;
use App\Core\Audit\AuditService;
use App\Core\Export\CsvExporter;
use App\Core\Notifications\NotificationService;
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
use Illuminate\Support\Facades\Validator;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

class SecurityIncidentController extends Controller
{
    public function __construct(
        protected NumberingService $numberingService,
        protected AuditService $auditService,
        protected ActivityService $activityService,
        protected NotificationService $notificationService,
    ) {
        $this->authorizeResource(SecurityIncident::class, 'security_incident');
    }

    public function index(Request $request): InertiaResponse
    {
        $query = SecurityIncident::query()
            ->with(['site', 'area', 'reporter', 'severity'])
            ->select('security_incidents.*');

        // Organization scope (fail closed — no implicit 'all' for users without core.scope.all)
        $user = $request->user();
        if ($user->can('core.scope.all')) {
            // full access
        } elseif ($user->employee?->site_id) {
            $query->where('security_incidents.site_id', $user->employee->site_id);
        } elseif ($user->employee) {
            $query->where('security_incidents.reported_by', $user->id);
        } else {
            $query->whereRaw('1 = 0');
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
            'filters' => $request->only(['site_id', 'type', 'status', 'search']),
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
                'security',
                $user,
                null,
                false
            );

            $data = $request->validated();
            $data['security_number'] = $securityNumber;
            $data['reported_by'] = $user->id;
            $data['status'] = 'reported';

            $incident = SecurityIncident::create($data);

            $this->auditService->log(
                'security.incident.created',
                $incident,
                [],
                ['security_number' => $incident->security_number],
                $user,
                'security',
                $incident->id
            );

            $this->activityService->log(
                'security',
                $incident->id,
                'created',
                "Security incident {$incident->security_number} reported by {$user->name}",
                $user
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
            'available_transitions' => $this->availableTransitions($securityIncident),
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
        // Closed incidents are terminal — cannot be edited via update.
        abort_if($securityIncident->status === 'closed', 403, 'Closed security incidents cannot be edited.');

        return DB::transaction(function () use ($request, $securityIncident) {
            $user = $request->user();
            $oldData = $securityIncident->getAttributes();

            $securityIncident->update($request->validated());

            $this->auditService->log(
                'security.incident.updated',
                $securityIncident,
                $oldData,
                $securityIncident->getAttributes(),
                $user,
                'security',
                $securityIncident->id
            );

            return redirect()->route('security.incidents.show', $securityIncident)
                ->with('success', 'Security incident updated successfully');
        });
    }

    /**
     * M10 WS-1: explicit transition workflow.
     *  - investigate: reported -> under_investigation
     *  - close:      under_investigation -> closed (requires resolution min 10)
     *  - closed is terminal (no transitions allowed)
     */
    public function transition(Request $request, SecurityIncident $securityIncident)
    {
        abort_if($securityIncident->status === 'closed', 403, 'Closed security incidents cannot be transitioned.');

        $validator = Validator::make($request->all(), [
            'action' => ['required', 'in:investigate,close'],
            'resolution' => ['required_if:action,close', 'string', 'min:10'],
        ]);

        if ($validator->fails()) {
            return redirect()->route('security.incidents.show', $securityIncident)
                ->withErrors($validator)
                ->withInput();
        }

        $action = $request->input('action');

        if ($action === 'investigate' && $securityIncident->status !== 'reported') {
            abort(422, 'Only reported incidents can be set under investigation.');
        }

        if ($action === 'close' && $securityIncident->status !== 'under_investigation') {
            abort(422, 'Only incidents under investigation can be closed.');
        }

        $user = $request->user();

        return DB::transaction(function () use ($request, $securityIncident, $action, $user) {
            if ($action === 'investigate') {
                $securityIncident->status = 'under_investigation';
            } elseif ($action === 'close') {
                $securityIncident->status = 'closed';
                $securityIncident->resolution = $request->input('resolution');
                $securityIncident->resolved_at = now();
            }

            $securityIncident->save();

            $this->auditService->log(
                "security.incident.{$action}",
                $securityIncident,
                [],
                ['status' => $securityIncident->status],
                $user,
                'security',
                $securityIncident->id
            );

            $this->activityService->log(
                'security',
                $securityIncident->id,
                $action,
                "Security incident {$securityIncident->security_number} {$action}d by {$user->name}",
                $user
            );

            if ($action === 'close' && $securityIncident->reporter) {
                $this->notificationService->notify(
                    $securityIncident->reporter,
                    'security.incident.closed',
                    [
                        'title' => 'Security Incident Closed',
                        'message' => "Security incident {$securityIncident->security_number} has been closed.",
                    ],
                    $user,
                    'security',
                    $securityIncident->id,
                    route('security.incidents.show', $securityIncident)
                );
            }

            return redirect()->route('security.incidents.show', $securityIncident)
                ->with('success', "Security incident {$action}d successfully");
        });
    }

    public function export(Request $request)
    {
        $this->authorize('export', SecurityIncident::class);

        $query = SecurityIncident::query()->with(['site', 'reporter', 'severity']);

        $user = $request->user();
        if ($user->can('core.scope.all')) {
            // full access
        } elseif ($user->employee?->site_id) {
            $query->where('security_incidents.site_id', $user->employee->site_id);
        } elseif ($user->employee) {
            $query->where('security_incidents.reported_by', $user->id);
        } else {
            $query->whereRaw('1 = 0');
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

    /**
     * @return array<int,string>
     */
    protected function availableTransitions(SecurityIncident $incident): array
    {
        return match ($incident->status) {
            'reported' => ['investigate'],
            'under_investigation' => ['close'],
            'closed' => [],
            default => [],
        };
    }
}
