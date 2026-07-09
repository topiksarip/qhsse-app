<?php

namespace App\Http\Controllers\Core;

use App\Core\Workflow\WorkflowService;
use App\Http\Controllers\Controller;
use App\Http\Requests\Core\WorkflowTransitionRunRequest;
use App\Models\Core\Workflow\WorkflowDefinition;
use App\Models\Core\Workflow\WorkflowHistory;
use App\Models\Core\Workflow\WorkflowInstance;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class WorkflowController extends Controller
{
    public function index(Request $request): Response
    {
        $definitions = WorkflowDefinition::query()
            ->withCount('transitions')
            ->when($request->string('search')->toString(), function ($query, string $search): void {
                $query->where(function ($query) use ($search): void {
                    $query->where('module_name', 'like', "%{$search}%")
                        ->orWhere('code', 'like', "%{$search}%")
                        ->orWhere('name', 'like', "%{$search}%");
                });
            })
            ->orderBy('module_name')
            ->paginate(10)
            ->withQueryString();

        $instances = WorkflowInstance::query()
            ->with('definition:id,name,module_name')
            ->latest()
            ->limit(10)
            ->get();

        return Inertia::render('Core/Workflow/Index', [
            'definitions' => $definitions,
            'instances' => $instances,
            'filters' => $request->only('search'),
        ]);
    }

    public function show(WorkflowDefinition $definition): Response
    {
        $definition->load(['transitions' => fn ($query) => $query->orderBy('from_status')->orderBy('id')]);

        return Inertia::render('Core/Workflow/Show', [
            'definition' => $definition,
        ]);
    }

    public function run(WorkflowTransitionRunRequest $request, WorkflowService $service): RedirectResponse
    {
        $moduleName = $request->string('module_name')->toString();
        $referenceId = $request->integer('reference_id');
        $actionKey = $request->string('action_key')->toString();

        if ($actionKey === '') {
            $service->start($moduleName, $referenceId, $request->user(), ['source' => 'workflow-ui']);
        } else {
            $service->transition(
                $moduleName,
                $referenceId,
                $actionKey,
                $request->user(),
                $request->string('reason')->toString() ?: null,
                ['source' => 'workflow-ui'],
            );
        }

        return redirect()->route('core.workflow.index');
    }

    public function history(Request $request): Response
    {
        $histories = WorkflowHistory::query()
            ->with('actor:id,name,email')
            ->when($request->string('module_name')->toString(), fn ($query, string $module) => $query->where('module_name', $module))
            ->when($request->integer('reference_id'), fn ($query, int $referenceId) => $query->where('reference_id', $referenceId))
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return Inertia::render('Core/Workflow/History', [
            'histories' => $histories,
            'filters' => $request->only(['module_name', 'reference_id']),
        ]);
    }
}
