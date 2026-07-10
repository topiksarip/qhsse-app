# API Contract — Reporting & Export

> Spec ini mendefinisikan semua route, request, response, dan error handling untuk modul Reporting & Export.

## 1. Route Table

Route dibagi menjadi 2 prefix: `/reports/templates` untuk template management dan `/reports/saved` (plus `/reports/create`) untuk report generation. Middleware: `auth,verified`.

### Resource Group 1: Templates (`reporting.templates.*`)

| Method | URI | Controller | Route Name | Permission | Description |
|---|---|---|---|---|---|
| GET | `/reports/templates` | `index` | `reporting.templates.index` | `reporting.templates.view` | List template laporan |
| GET | `/reports/templates/create` | `create` | `reporting.templates.create` | `reporting.templates.create` | Render form buat custom template |
| POST | `/reports/templates` | `store` | `reporting.templates.store` | `reporting.templates.create` | Simpan custom template baru |
| GET | `/reports/templates/{template}` | `show` | `reporting.templates.show` | `reporting.templates.view` | Detail template |
| GET | `/reports/templates/{template}/edit` | `edit` | `reporting.templates.edit` | `reporting.templates.update` | Render form edit template |
| PUT | `/reports/templates/{template}` | `update` | `reporting.templates.update` | `reporting.templates.update` | Update template |
| PATCH | `/reports/templates/{template}/toggle-active` | `toggleActive` | `reporting.templates.toggleActive` | `reporting.templates.update` | Aktifkan/nonaktifkan template |

### Resource Group 2: Reports (`reporting.reports.*`)

| Method | URI | Controller | Route Name | Permission | Description |
|---|---|---|---|---|---|
| GET | `/reports/saved` | `index` | `reporting.reports.index` | `reporting.reports.view` | List saved reports |
| GET | `/reports/create` | `create` | `reporting.reports.create` | `reporting.reports.generate` | Render configure page |
| POST | `/reports` | `store` | `reporting.reports.store` | `reporting.reports.generate` | Submit generate (dispatch job) |
| GET | `/reports/saved/{report}` | `show` | `reporting.reports.show` | `reporting.reports.view` | Detail saved report |
| GET | `/reports/saved/{report}/download` | `download` | `reporting.reports.download` | `reporting.reports.download` | Download report file |
| DELETE | `/reports/saved/{report}` | `destroy` | `reporting.reports.destroy` | `reporting.reports.view` | Delete saved report |

### Route Registration

File: `routes/modules.php`

```php
use App\Http\Controllers\Modules\Reporting\ReportTemplateController;
use App\Http\Controllers\Modules\Reporting\SavedReportController;

Route::middleware(['auth', 'verified'])
    ->name('reporting.')
    ->group(function (): void {
        // Template routes
        Route::prefix('reports/templates')
            ->name('templates.')
            ->group(function (): void {
                Route::get('/', [ReportTemplateController::class, 'index'])
                    ->name('index')
                    ->middleware('permission:reporting.templates.view');

                Route::get('/create', [ReportTemplateController::class, 'create'])
                    ->name('create')
                    ->middleware('permission:reporting.templates.create');

                Route::post('/', [ReportTemplateController::class, 'store'])
                    ->name('store')
                    ->middleware('permission:reporting.templates.create');

                Route::get('/{template}', [ReportTemplateController::class, 'show'])
                    ->name('show')
                    ->middleware('permission:reporting.templates.view');

                Route::get('/{template}/edit', [ReportTemplateController::class, 'edit'])
                    ->name('edit')
                    ->middleware('permission:reporting.templates.update');

                Route::put('/{template}', [ReportTemplateController::class, 'update'])
                    ->name('update')
                    ->middleware('permission:reporting.templates.update');

                Route::patch('/{template}/toggle-active', [ReportTemplateController::class, 'toggleActive'])
                    ->name('toggleActive')
                    ->middleware('permission:reporting.templates.update');
            });

        // Report routes
        Route::get('/reports/create', [SavedReportController::class, 'create'])
            ->name('reports.create')
            ->middleware('permission:reporting.reports.generate');

        Route::post('/reports', [SavedReportController::class, 'store'])
            ->name('reports.store')
            ->middleware('permission:reporting.reports.generate');

        Route::prefix('reports/saved')
            ->name('reports.')
            ->group(function (): void {
                Route::get('/', [SavedReportController::class, 'index'])
                    ->name('index')
                    ->middleware('permission:reporting.reports.view');

                Route::get('/{report}', [SavedReportController::class, 'show'])
                    ->name('show')
                    ->middleware('permission:reporting.reports.view');

                Route::get('/{report}/download', [SavedReportController::class, 'download'])
                    ->name('download')
                    ->middleware('permission:reporting.reports.download');

                Route::delete('/{report}', [SavedReportController::class, 'destroy'])
                    ->name('destroy')
                    ->middleware('permission:reporting.reports.view');
            });
    });
```

### Route Model Binding

- `{template}` → `ReportTemplate` model via `id`.
- `{report}` → `SavedReport` model via `id`.

---

## 2. Request Payloads

### POST `/reports/templates` (store — create custom template)

```json
{
  "name": "Laporan Custom Insiden & CAPA",
  "description": "Template custom untuk laporan gabungan insiden dan CAPA per site.",
  "is_active": true,
  "config": {
    "sections": [
      {
        "key": "summary",
        "label": "Ringkasan",
        "enabled": true
      },
      {
        "key": "incident_stats",
        "label": "Statistik Insiden",
        "enabled": true,
        "data_source": "incident",
        "group_by": ["severity", "site"]
      },
      {
        "key": "capa_stats",
        "label": "Status CAPA",
        "enabled": true,
        "data_source": "capa",
        "group_by": ["status", "priority"]
      }
    ],
    "default_parameters": {
      "format": "pdf",
      "include_charts": true
    }
  }
}
```

**Validation Rules (StoreReportTemplateRequest):**

| Field | Rule | Notes |
|---|---|---|
| `name` | `required\|string\|max:255` | |
| `description` | `nullable\|string\|max:2000` | |
| `is_active` | `boolean` | Default `true` |
| `config` | `required\|array` | |
| `config.sections` | `required\|array\|min:1` | Minimal 1 section |
| `config.sections.*.key` | `required\|string\|max:100` | |
| `config.sections.*.label` | `required\|string\|max:255` | |
| `config.sections.*.enabled` | `boolean` | |
| `config.sections.*.data_source` | `nullable\|string\|in:incident,capa,inspection,audit,training,permit,environment,security,quality,risk,legal,emergency,contractor,asset,communication` | |
| `config.default_parameters` | `nullable\|array` | |
| `config.default_parameters.format` | `nullable\|in:csv,pdf,excel` | |

**Controller behavior (store):**

1. Validate request
2. Force `type = 'custom'` (ignore any type in request)
3. Create `ReportTemplate` with `created_by` = auth user
4. `AuditService::created($template, $actor, 'reporting', $template->id)`
5. `ActivityService::log('reporting', $template->id, 'template.created', 'Template custom dibuat', $actor)`
6. Redirect to `reporting.templates.show`

### PUT `/reports/templates/{template}` (update)

```json
{
  "name": "Laporan Custom Insiden & CAPA (Updated)",
  "description": "Deskripsi yang diperbarui.",
  "is_active": true,
  "config": {
    "sections": [
      {
        "key": "summary",
        "label": "Ringkasan Eksekutif",
        "enabled": true
      }
    ],
    "default_parameters": {
      "format": "excel"
    }
  }
}
```

**Validation Rules (UpdateReportTemplateRequest):**

Same as store, but with additional rules:

- If template is pre-defined (`type != 'custom'`):
  - `name` → ignored (cannot change)
  - `type` → ignored (cannot change)
  - `config` → ignored (cannot change)
  - Only `description` and `is_active` can be updated.
- If template is custom:
  - All fields can be updated.

**Controller behavior (update):**

1. Validate request
2. If pre-defined: only update `description` and `is_active`
3. If custom: update all allowed fields
4. `AuditService::updated($template, $oldValues, $actor, 'reporting', $template->id)`
5. `ActivityService::log('reporting', $template->id, 'template.updated', 'Template diperbarui', $actor)`
6. Redirect to `reporting.templates.show`

### PATCH `/reports/templates/{template}/toggle-active` (toggleActive)

No request body needed. Controller:

1. Toggle `is_active` field
2. `AuditService::log('template.activated' atau 'template.deactivated', ...)`
3. Redirect back with success message

### POST `/reports` (store — generate report)

```json
{
  "report_template_id": 6,
  "name": "Laporan Bulanan QHSSE - Januari 2026",
  "parameters": {
    "date_from": "2026-01-01",
    "date_to": "2026-01-31",
    "site_id": 1,
    "department_id": null,
    "format": "pdf",
    "include_charts": true
  }
}
```

**Validation Rules (GenerateReportRequest):**

| Field | Rule | Notes |
|---|---|---|
| `report_template_id` | `required\|exists:report_templates,id` | Template must exist and be active |
| `name` | `required\|string\|max:255` | |
| `parameters` | `required\|array` | |
| `parameters.date_from` | `required\|date` | |
| `parameters.date_to` | `required\|date\|after_or_equal:date_from` | |
| `parameters.site_id` | `nullable\|exists:sites,id` | If null, all sites in scope |
| `parameters.department_id` | `nullable\|exists:departments,id` | Must belong to site_id if both set |
| `parameters.format` | `required\|in:csv,pdf,excel` | |
| `parameters.include_charts` | `boolean` | Default false, ignored for CSV |

**Custom Validation (after method):**

```php
public function after(): array
{
    return [
        function (Validator $validator) {
            // Check date range max 730 days
            if ($this->parameters['date_from'] && $this->parameters['date_to']) {
                $from = Carbon::parse($this->parameters['date_from']);
                $to = Carbon::parse($this->parameters['date_to']);
                if ($from->diffInDays($to) > 730) {
                    $validator->errors()->add(
                        'parameters.date_to',
                        'Rentang tanggal maksimal 2 tahun (730 hari).'
                    );
                }
            }

            // Check template is active
            $template = ReportTemplate::find($this->report_template_id);
            if ($template && !$template->is_active) {
                $validator->errors()->add(
                    'report_template_id',
                    'Template ini tidak aktif.'
                );
            }

            // Check department belongs to site
            if ($this->parameters['site_id'] && $this->parameters['department_id']) {
                $dept = Department::find($this->parameters['department_id']);
                if ($dept && $dept->site_id !== (int) $this->parameters['site_id']) {
                    $validator->errors()->add(
                        'parameters.department_id',
                        'Departemen tidak terdaftar di site yang dipilih.'
                    );
                }
            }
        },
    ];
}
```

**Controller behavior (store):**

1. Validate request
2. Apply data scope: if user scope is `site`, force `site_id` to user's site; if `department`, force `department_id` to user's department
3. Create `SavedReport` with:
   - `report_template_id` = request template
   - `name` = request name
   - `parameters` = request parameters
   - `generated_by` = auth user
   - `generated_at` = now()
   - `format` = parameters.format
   - `status` = `'pending'`
   - `file_path` = NULL
4. Dispatch `GenerateReportJob::dispatch($savedReport->id)`
5. `AuditService::created($savedReport, $actor, 'reporting', $savedReport->id)`
6. `ActivityService::log('reporting', $savedReport->id, 'report.generated', 'Laporan dibuat: ' . $savedReport->name, $actor)`
7. Redirect to `reporting.reports.show` with success flash

### GET `/reports/saved/{report}/download` (download)

No request body. Controller:

1. Check `report.status === 'completed'` and `report.file_path !== null`
2. Check user has `reporting.reports.download` permission
3. Check data scope (user can only download reports within their scope)
4. Log download activity: `ActivityService::log('reporting', $report->id, 'report.downloaded', 'Laporan diunduh', $actor)`
5. `AuditService::log('report.downloaded', ...)`
6. Return file download response (private disk)

```php
public function download(SavedReport $report)
{
    $this->authorize('download', $report);

    if (!$report->isDownloadable()) {
        return back()->withErrors([
            'download' => 'Laporan belum siap untuk diunduh atau gagal di-generate.'
        ]);
    }

    $file = Storage::disk('local')->get($report->file_path);
    $mimeType = match($report->format) {
        'csv' => 'text/csv',
        'pdf' => 'application/pdf',
        'excel' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
    };
    $extension = $report->format === 'excel' ? 'xlsx' : $report->format;
    $filename = Str::slug($report->name) . '.' . $extension;

    ActivityService::log('reporting', $report->id, 'report.downloaded', 'Laporan diunduh', auth()->user());
    AuditService::log('report.downloaded', $report, null, null, auth()->user(), 'reporting', $report->id);

    return response($file, 200, [
        'Content-Type' => $mimeType,
        'Content-Disposition' => 'attachment; filename="' . $filename . '"',
    ]);
}
```

### DELETE `/reports/saved/{report}` (destroy)

No request body. Controller:

1. Check user permission (only Admin / Super Admin / QHSSE Manager can delete)
2. If `file_path` exists, delete file from disk
3. Delete `SavedReport` record
4. `AuditService::log('report.deleted', ...)`
5. Redirect to `reporting.reports.index`

---

## 3. Inertia Response Props

### Template Index Page (`Reporting/Templates/Index.tsx`)

```typescript
{
  templates: {
    data: ReportTemplateListItem[],
    current_page: number,
    last_page: number,
    per_page: number,
    total: number,
  },
  filters: {
    search: string,
    type: string | null,
    is_active: boolean | null,
  },
  can: {
    create: boolean,
  },
}
```

### Template Create/Edit Page (`Reporting/Templates/Form.tsx`)

```typescript
{
  template: ReportTemplate | null, // null on create
  can: {
    update: boolean,
  },
}
```

### Configure Report Page (`Reporting/Reports/Configure.tsx`)

```typescript
{
  template: ReportTemplate & {
    created_by: { id: number; name: string },
    config: {
      sections: {
        key: string,
        label: string,
        enabled: boolean,
        data_source?: string,
      }[],
      default_parameters: {
        date_range?: string,
        site_id?: number | null,
        department_id?: number | null,
        format?: string,
        include_charts?: boolean,
      },
    },
  },
  sites: Site[],
  departments: Department[],
  can: {
    generate: boolean,
  },
}
```

### Saved Reports Index Page (`Reporting/Reports/Index.tsx`)

```typescript
{
  reports: {
    data: SavedReportListItem[],
    current_page: number,
    last_page: number,
    per_page: number,
    total: number,
  },
  filters: {
    search: string,
    type: string | null,
    status: string | null,
    format: string | null,
    from: string | null,
    to: string | null,
  },
  stats: {
    total: number,
    pending: number,
    processing: number,
    completed: number,
    failed: number,
  },
  can: {
    generate: boolean,
    download: boolean,
  },
}
```

### Saved Report Show Page (`Reporting/Reports/Show.tsx`)

```typescript
{
  report: SavedReport & {
    report_template: ReportTemplate,
    generated_by: { id: number; name: string },
  },
  can: {
    download: boolean,
    generate: boolean,
    delete: boolean,
  },
}
```

---

## 4. ListQuery Parameters

### Template Index

| Parameter | Type | Default | Description |
|---|---|---|---|
| `search` | string | `''` | Searches `name` and `description` |
| `type` | string | `null` | Filter by report type |
| `is_active` | bool | `null` | Filter active/inactive |
| `per_page` | int | `15` | Items per page (max 100) |
| `sort` | string | `created_at` | Sort column |
| `direction` | string | `desc` | Sort direction |

### Saved Reports Index

| Parameter | Type | Default | Description |
|---|---|---|---|
| `search` | string | `''` | Searches `name` |
| `type` | string | `null` | Filter by template type (joins report_templates) |
| `status` | string | `null` | Filter by status: pending, processing, completed, failed |
| `format` | string | `null` | Filter by format: csv, pdf, excel |
| `from` | date | `null` | Filter generated_at >= from |
| `to` | date | `null` | Filter generated_at <= to |
| `per_page` | int | `15` | Items per page (max 100) |
| `sort` | string | `generated_at` | Sort column |
| `direction` | string | `desc` | Sort direction |

### Controller index method pattern (Saved Reports):

```php
public function index(ListQuery $listQuery): Response
{
    $items = $listQuery->paginate(
        SavedReport::query()
            ->with(['reportTemplate', 'generatedBy'])
            ->when($listQuery->filters('type'), function ($query, $type) {
                $query->whereHas('reportTemplate', function ($q) use ($type) {
                    $q->where('type', $type);
                });
            })
            ->when($listQuery->filters('status'), function ($query, $status) {
                $query->where('status', $status);
            })
            ->when($listQuery->filters('format'), function ($query, $format) {
                $query->where('format', $format);
            }),
        ['name'],
        ['generated_at', 'created_at'],
        'generated_at',
        15,
    );

    // Apply data scope
    $items->getCollection()->transform(function ($item) {
        $item->is_downloadable = $item->isDownloadable();
        return $item;
    });

    // Stats for KPI cards
    $stats = [
        'total'      => SavedReport::count(),
        'pending'    => SavedReport::where('status', 'pending')->count(),
        'processing' => SavedReport::where('status', 'processing')->count(),
        'completed'  => SavedReport::where('status', 'completed')->count(),
        'failed'     => SavedReport::where('status', 'failed')->count(),
    ];

    return Inertia::render('Modules/Reporting/Reports/Index', [
        'reports' => $items,
        'filters' => $listQuery->filters(),
        'stats'   => $stats,
        'can'     => [
            'generate' => auth()->user()->can('reporting.reports.generate'),
            'download' => auth()->user()->can('reporting.reports.download'),
        ],
    ]);
}
```

---

## 5. GenerateReportJob Specification

### Job Class

File: `app/Jobs/Modules/Reporting/GenerateReportJob.php`

```php
<?php

namespace App\Jobs\Modules\Reporting;

use App\Models\Modules\Reporting\SavedReport;
use App\Services\Modules\Reporting\ReportGeneratorService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class GenerateReportJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;
    public int $timeout = 300;

    public function __construct(
        public int $savedReportId
    ) {}

    public function handle(ReportGeneratorService $generator): void
    {
        $report = SavedReport::findOrFail($this->savedReportId);

        // Update status to processing
        $report->update(['status' => SavedReport::STATUS_PROCESSING]);

        try {
            // Generate the report file
            $filePath = $generator->generate($report);

            // Update status to completed
            $report->update([
                'status'    => SavedReport::STATUS_COMPLETED,
                'file_path' => $filePath,
            ]);

            // Log activity
            ActivityService::log('reporting', $report->id, 'report.completed',
                'Laporan selesai: ' . $report->name, $report->generatedBy);

            // Send notification
            NotificationService::notify(
                $report->generatedBy,
                'report.completed',
                [
                    'report_name' => $report->name,
                    'format'      => $report->format,
                    'report_id'   => $report->id,
                ],
                null,
                'reporting',
                $report->id,
                route('reporting.reports.show', $report->id),
            );

            // Audit log
            AuditService::log('report.completed', $report, null,
                ['status' => 'completed', 'file_path' => $filePath],
                $report->generatedBy, 'reporting', $report->id);

        } catch (\Exception $e) {
            // Update status to failed
            $parameters = $report->parameters;
            $parameters['error'] = $e->getMessage();

            $report->update([
                'status'     => SavedReport::STATUS_FAILED,
                'parameters' => $parameters,
                'file_path'  => null,
            ]);

            // Log activity
            ActivityService::log('reporting', $report->id, 'report.failed',
                'Laporan gagal: ' . $report->name, $report->generatedBy,
                ['error' => $e->getMessage()]);

            // Send notification
            NotificationService::notify(
                $report->generatedBy,
                'report.failed',
                [
                    'report_name'  => $report->name,
                    'error_message' => $e->getMessage(),
                    'report_id'    => $report->id,
                ],
                null,
                'reporting',
                $report->id,
                route('reporting.reports.show', $report->id),
            );

            // Audit log
            AuditService::log('report.failed', $report, null,
                ['status' => 'failed', 'error' => $e->getMessage()],
                $report->generatedBy, 'reporting', $report->id);
        }
    }
}
```

### ReportGeneratorService

File: `app/Services/Modules/Reporting/ReportGeneratorService.php`

```php
<?php

namespace App\Services\Modules\Reporting;

use App\Models\Modules\Reporting\SavedReport;
use App\Core\File\CsvExporter;
use Illuminate\Support\Facades\Storage;

class ReportGeneratorService
{
    public function __construct(
        private CsvExporter $csvExporter,
        private PdfReportGenerator $pdfGenerator,
        private ExcelReportGenerator $excelGenerator,
    ) {}

    public function generate(SavedReport $report): string
    {
        // Collect data based on template type
        $data = $this->collectData($report);

        // Generate based on format
        $filePath = match ($report->format) {
            'csv'   => $this->generateCsv($report, $data),
            'pdf'   => $this->generatePdf($report, $data),
            'excel' => $this->generateExcel($report, $data),
        };

        return $filePath;
    }

    private function collectData(SavedReport $report): array
    {
        $parameters = $report->parameters;
        $template = $report->reportTemplate;

        return match ($template->type) {
            'incident_summary'     => $this->collectIncidentData($parameters),
            'capa_summary'         => $this->collectCapaData($parameters),
            'inspection_summary'   => $this->collectInspectionData($parameters),
            'audit_summary'        => $this->collectAuditData($parameters),
            'training_compliance'  => $this->collectTrainingData($parameters),
            'monthly_qhsse'        => $this->collectMonthlyQhsseData($parameters),
            'annual_qhsse'         => $this->collectAnnualQhsseData($parameters),
            'custom'               => $this->collectCustomData($template->config, $parameters),
        };
    }

    private function collectMonthlyQhsseData(array $params): array
    {
        // Aggregate from ALL modules
        return [
            'incidents'   => $this->collectIncidentData($params),
            'capas'       => $this->collectCapaData($params),
            'inspections' => $this->collectInspectionData($params),
            'audits'      => $this->collectAuditData($params),
            'trainings'   => $this->collectTrainingData($params),
            'permits'     => $this->collectPermitData($params),
            'environment' => $this->collectEnvironmentData($params),
            'security'    => $this->collectSecurityData($params),
        ];
    }
}
```

---

## 6. Error Responses

| Status | When | Response |
|---|---|---|
| `403` | User lacks required permission | Inertia redirects to dashboard with error flash |
| `404` | Template/Report ID not found | Laravel default 404 page |
| `422` | Validation failure | Inertia sends errors back to form with `$page.props.errors` |
| `400` | Template is not active | Error: "Template ini tidak aktif." |
| `400` | Report not downloadable (not completed) | Error: "Laporan belum siap untuk diunduh atau gagal di-generate." |
| `400` | Date range exceeds 730 days | Error: "Rentang tanggal maksimal 2 tahun (730 hari)." |
| `419` | CSRF token expired | Laravel default |
| `500` | Report generation job fails | Status set to `failed`, notification sent, error stored in parameters JSON |

### Report generation failure handling:

```php
// In GenerateReportJob::handle()
try {
    $filePath = $generator->generate($report);
    $report->update([
        'status'    => SavedReport::STATUS_COMPLETED,
        'file_path' => $filePath,
    ]);
    // ... notifications ...
} catch (\Exception $e) {
    $parameters = $report->parameters;
    $parameters['error'] = $e->getMessage();
    $report->update([
        'status'     => SavedReport::STATUS_FAILED,
        'parameters' => $parameters,
        'file_path'  => null,
    ]);
    // ... notifications ...
}
```

---

## 7. Integration Points

### Data Sources (Read-Only Queries)

The `ReportGeneratorService` reads from ALL module tables:

| Module | Tables Read | Data Collected |
|---|---|---|
| Incident | `incidents` | Count by severity, type, site, status, monthly trend |
| Investigation | `investigations` | Investigation completion rate |
| CAPA | `capa_actions` | Count by status, priority, source, overdue, closure rate |
| Inspection | `inspections`, `inspection_items` | Total, pass/fail rate, findings |
| Audit | `audit_findings` | Findings by severity, status, closure rate |
| Training | `training_records`, `training_enrollments` | Enrollment, completion rate, overdue |
| Permit | `permits` | Active, expired, by type |
| Environment | `environmental_incidents` | Incidents, compliance |
| Security | `security_incidents` | Incidents, patrols |
| Quality | `ncr_records` | NCR count, closure |
| Risk | `risk_registers` | Risk count by level, status |
| Legal | `legal_compliance_items` | Compliance status |
| Emergency | `emergency_incidents` | Incidents, response time |
| Contractor | `contractors` | Active, compliance |
| Asset | `assets` | Total, status |
| Communication | `communications` | Sent, by type |

### File Storage

| Operation | Service | Detail |
|---|---|---|
| Store generated file | `Storage::disk('local')` | Private disk, path: `reports/{id}/{filename}.{ext}` |
| Download file | `response()->download()` | Via authorized controller method |
| Delete file | `Storage::disk('local')->delete()` | When saved_report is deleted |

### Queue

| Property | Value |
|---|---|
| Queue connection | `redis` (from config) |
| Queue name | `default` (or `reports` for dedicated queue) |
| Tries | 1 (no retry — fail immediately and notify) |
| Timeout | 300 seconds |

### Schedule (Future)

```php
// app/Console/Kernel.php — deferred to Phase 6
// Schedule::command('reports:auto-generate')->dailyAt('01:00');
```
