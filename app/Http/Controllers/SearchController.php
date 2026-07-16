<?php

namespace App\Http\Controllers;

use App\Models\Modules\Asset\Asset;
use App\Models\Modules\Audit\Audit;
use App\Models\Modules\Capa\CapaAction;
use App\Models\Modules\DocumentControl\ControlledDocument;
use App\Models\Modules\Incident\IncidentReport;
use App\Models\Modules\Inspection\Inspection;
use App\Models\Modules\Permit\Permit;
use App\Models\Modules\RiskManagement\RiskRegister;
use App\Models\Modules\Security\SecurityIncident;
use App\Models\Modules\Training\TrainingProgram;
use App\Models\Modules\Training\TrainingRecord;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class SearchController extends Controller
{
    private const PER_MODULE_LIMIT = 30;

    /**
     * Cross-module global search definition.
     *
     * Each entry: permission required to search that module, the Eloquent model,
     * the columns to LIKE-match, the show route name, the module label, and a
     * snippet closure. Results are permission-gated and capped per module.
     *
     * @return array<int, array{permission: string, model: class-string, columns: string[], route: string, label: string, snippet: callable}>
     */
    private static function modules(): array
    {
        return [
            [
                'permission' => 'incident.reports.view',
                'model' => IncidentReport::class,
                'columns' => ['incident_number', 'title', 'category', 'description'],
                'route' => 'incident.reports.show',
                'label' => 'Laporan Insiden',
                'snippet' => fn (IncidentReport $m) => 'No. ' . ($m->incident_number ?? '-') . ' • ' . ($m->status ?? ''),
            ],
            [
                'permission' => 'capa.actions.view',
                'model' => CapaAction::class,
                'columns' => ['action_number', 'title', 'description', 'source_module'],
                'route' => 'capa.actions.show',
                'label' => 'CAPA / Action',
                'snippet' => fn (CapaAction $m) => 'No. ' . ($m->action_number ?? '-') . ' • ' . ($m->status ?? ''),
            ],
            [
                'permission' => 'audit.management.view',
                'model' => Audit::class,
                'columns' => ['audit_number', 'title', 'audit_type', 'scope', 'summary'],
                'route' => 'audits.show',
                'label' => 'Audit Management',
                'snippet' => fn (Audit $m) => 'No. ' . ($m->audit_number ?? '-') . ' • ' . ($m->audit_type ?? ''),
            ],
            [
                'permission' => 'inspection.checklists.view',
                'model' => Inspection::class,
                'columns' => ['inspection_number', 'status', 'notes'],
                'route' => 'inspection.checklists.show',
                'label' => 'Inspeksi',
                'snippet' => fn (Inspection $m) => 'No. ' . ($m->inspection_number ?? '-') . ' • ' . ($m->status ?? ''),
            ],
            [
                'permission' => 'document.control.view',
                'model' => ControlledDocument::class,
                'columns' => ['document_number', 'title', 'type', 'revision_notes'],
                'route' => 'document.control.show',
                'label' => 'Document Control',
                'snippet' => fn (ControlledDocument $m) => 'No. ' . ($m->document_number ?? '-') . ' • v' . ($m->version ?? '1') . ' • ' . ($m->status ?? ''),
            ],
            [
                'permission' => 'permit.work.view',
                'model' => Permit::class,
                'columns' => ['permit_number', 'title', 'type', 'work_location', 'description'],
                'route' => 'permit.work.show',
                'label' => 'Izin Kerja',
                'snippet' => fn (Permit $m) => 'No. ' . ($m->permit_number ?? '-') . ' • ' . ($m->type ?? ''),
            ],
            [
                'permission' => 'security.incidents.view',
                'model' => SecurityIncident::class,
                'columns' => ['security_number', 'title', 'type', 'description'],
                'route' => 'security.incidents.show',
                'label' => 'Insiden Keamanan',
                'snippet' => fn (SecurityIncident $m) => 'No. ' . ($m->security_number ?? '-') . ' • ' . ($m->type ?? ''),
            ],
            [
                'permission' => 'risk.registers.view',
                'model' => RiskRegister::class,
                'columns' => ['register_number', 'title', 'type', 'activity', 'hazard'],
                'route' => 'risk.registers.show',
                'label' => 'Risk Register',
                'snippet' => fn (RiskRegister $m) => 'No. ' . ($m->register_number ?? '-') . ' • ' . ($m->type ?? ''),
            ],
            [
                'permission' => 'asset.management.view',
                'model' => Asset::class,
                'columns' => ['asset_number', 'name', 'category', 'serial_number', 'model', 'manufacturer'],
                'route' => 'assets.show',
                'label' => 'Asset & Equipment',
                'snippet' => fn (Asset $m) => 'No. ' . ($m->asset_number ?? '-') . ' • ' . ($m->category ?? ''),
            ],
            [
                'permission' => 'training.programs.view',
                'model' => TrainingProgram::class,
                'columns' => ['code', 'name', 'description', 'category'],
                'route' => 'training.programs.show',
                'label' => 'Program Pelatihan',
                'snippet' => fn (TrainingProgram $m) => 'Kode ' . ($m->code ?? '-') . ' • ' . ($m->category ?? ''),
            ],
            [
                'permission' => 'training.records.view',
                'model' => TrainingRecord::class,
                'columns' => ['training_number', 'provider', 'status', 'certificate_number', 'notes'],
                'route' => 'training.records.show',
                'label' => 'Record Pelatihan',
                'snippet' => fn (TrainingRecord $m) => 'No. ' . ($m->training_number ?? '-') . ' • ' . ($m->status ?? ''),
            ],
        ];
    }

    public function index(Request $request): Response
    {
        $q = trim((string) $request->query('q', ''));
        $scope = (string) $request->query('module', 'all');

        $results = [];
        $total = 0;
        $elapsedMs = 0;

        if ($q !== '') {
            $start = microtime(true);
            $allowed = $this->allowedModules();

            foreach ($allowed as $cfg) {
                if ($scope !== 'all' && $cfg['label'] !== $scope) {
                    continue;
                }

                $model = $cfg['model'];
                $match = $cfg['columns'];

                $items = $model::query()
                    ->where(function ($query) use ($match, $q) {
                        foreach ($match as $i => $col) {
                            $method = $i === 0 ? 'whereRaw' : 'orWhereRaw';
                            $query->{$method}('LOWER(' . $col . ') LIKE LOWER(?)', ['%' . $q . '%']);
                        }
                    })
                    ->orderBy('id', 'desc')
                    ->limit(self::PER_MODULE_LIMIT)
                    ->get();

                if ($items->isEmpty()) {
                    continue;
                }

                $rows = $items->map(function ($item) use ($cfg) {
                    return [
                        'id' => $item->getKey(),
                        'title' => $this->primaryTitle($item, $cfg),
                        'snippet' => (string) ($cfg['snippet'])($item),
                        'href' => route($cfg['route'], $item->getKey(), false),
                    ];
                })->all();

                $total += count($rows);
                $results[] = [
                    'module' => $cfg['label'],
                    'route' => $cfg['route'],
                    'items' => $rows,
                ];
            }

            $elapsedMs = (int) round((microtime(true) - $start) * 1000);
        }

        $moduleOptions = ['all' => 'Semua Modul'];
        foreach ($this->allowedModules() as $cfg) {
            $moduleOptions[$cfg['label']] = $cfg['label'];
        }

        return Inertia::render('Search/Index', [
            'query' => $q,
            'scope' => $scope,
            'moduleOptions' => $moduleOptions,
            'results' => $results,
            'total' => $total,
            'elapsedMs' => $elapsedMs,
            'searched' => $q !== '',
        ]);
    }

    /**
     * Pick the best display title for a result (falls back across common columns).
     */
    private function primaryTitle($item, array $cfg): string
    {
        foreach (['title', 'name', 'document_number', 'incident_number', 'action_number', 'audit_number', 'permit_number', 'security_number', 'register_number', 'asset_number', 'training_number', 'code'] as $candidate) {
            if (!empty($item->{$candidate})) {
                return (string) $item->{$candidate};
            }
        }

        return '#' . $item->getKey();
    }

    /**
     * Return only the modules the current user may view.
     *
     * @return array<int, array>
     */
    private function allowedModules(): array
    {
        $user = Auth::user();

        return collect(self::modules())
            ->filter(fn ($cfg) => $user && $user->can($cfg['permission']))
            ->values()
            ->all();
    }
}
