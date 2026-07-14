<?php

namespace App\Jobs\Modules\Reporting;

use App\Core\Notifications\NotificationService;
use App\Models\Modules\Audit\Audit;
use App\Models\Modules\Capa\CapaAction;
use App\Models\Modules\Incident\IncidentReport;
use App\Models\Modules\Inspection\Inspection;
use App\Models\Modules\Reporting\SavedReport;
use App\Models\Modules\Training\TrainingRecord;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use ZipArchive;

class GenerateReportJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 600;

    public int $tries = 1;

    public function __construct(
        public SavedReport $report,
        public User $user
    ) {}

    public function handle(): void
    {
        try {
            Log::info("GenerateReportJob started for report ID: {$this->report->id}");

            // Mark as processing
            $this->report->markAsProcessing();

            $this->generateReport();

            // Mark as completed
            Log::info("GenerateReportJob completed for report ID: {$this->report->id}");

            // Send success notification
            $this->sendCompletedNotification();

        } catch (\Exception $e) {
            Log::error("GenerateReportJob failed for report ID: {$this->report->id}", [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            // Mark as failed
            $this->report->markAsFailed($e->getMessage());

            // Send failure notification
            $this->sendFailedNotification($e->getMessage());
        }
    }

    protected function generateReport(): void
    {
        // Get template and parameters
        $template = $this->report->template;
        $params = $this->report->parameters;

        Log::info("Generating report: {$this->report->name}", [
            'template' => $template->type,
            'format' => $this->report->format,
            'parameters' => $params,
        ]);

        // Generate file based on format
        $filePath = $this->createReportFile();

        // Get file size
        $fileSize = Storage::size($filePath);

        // Mark as completed with file info
        $this->report->markAsCompleted($filePath, $fileSize);

        Log::info("Report file created: {$filePath} ({$fileSize} bytes)");
    }

    protected function createReportFile(): string
    {
        $directory = "reports/{$this->report->id}";
        Storage::makeDirectory($directory);

        $filename = $this->report->getDownloadFileName();
        $filePath = "{$directory}/{$filename}";

        $csvContent = $this->generateCsvContent();

        $content = match ($this->report->format) {
            'csv' => $csvContent,
            'pdf' => $this->generatePdfContent($csvContent),
            'excel' => $this->generateExcelContent($csvContent),
            default => throw new RuntimeException("Unsupported report format: {$this->report->format}"),
        };

        Storage::put($filePath, $content);

        return $filePath;
    }

    protected function generateCsvContent(): string
    {
        $template = $this->report->template;
        $params = $this->report->parameters;

        // Dispatch to specific report type generator
        return match ($template->type) {
            'incident_summary' => $this->generateIncidentSummaryCsv($params),
            'capa_summary' => $this->generateCapaEffectivenessCsv($params),
            'inspection_summary' => $this->generateInspectionSummaryCsv($params),
            'audit_summary' => $this->generateAuditFindingsCsv($params),
            'training_compliance' => $this->generateTrainingRecordsCsv($params),
            'monthly_qhsse', 'annual_qhsse' => $this->generateSafetyMetricsCsv($params),
            'custom' => $this->generateCustomCsv($params),
            default => throw new RuntimeException("Unsupported report type: {$template->type}"),
        };
    }

    protected function generatePdfContent(string $csvContent): string
    {
        $lines = [];
        foreach ($this->parseCsvRows($csvContent) as $row) {
            $wrapped = wordwrap(implode(' | ', $row), 105, "\n", true);
            array_push($lines, ...explode("\n", $wrapped));
        }

        $pages = array_chunk($lines ?: ['QHSSE Report'], 58);
        $fontObjectId = 3 + (count($pages) * 2);
        $pageReferences = array_map(
            fn (int $index): string => (3 + ($index * 2)).' 0 R',
            array_keys($pages)
        );
        $objects = [
            1 => '<< /Type /Catalog /Pages 2 0 R >>',
            2 => '<< /Type /Pages /Kids ['.implode(' ', $pageReferences).'] /Count '.count($pages).' >>',
        ];

        foreach ($pages as $index => $pageLines) {
            $pageId = 3 + ($index * 2);
            $contentId = $pageId + 1;
            $stream = "BT\n/F1 9 Tf\n40 800 Td\n11 TL\n";
            foreach ($pageLines as $line) {
                $escaped = str_replace(['\\', '(', ')'], ['\\\\', '\\(', '\\)'], $line);
                $stream .= "({$escaped}) Tj\nT*\n";
            }
            $stream .= "ET\n";

            $objects[$pageId] = "<< /Type /Page /Parent 2 0 R /MediaBox [0 0 595 842] /Resources << /Font << /F1 {$fontObjectId} 0 R >> >> /Contents {$contentId} 0 R >>";
            $objects[$contentId] = '<< /Length '.strlen($stream).">>\nstream\n{$stream}endstream";
        }
        $objects[$fontObjectId] = '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>';
        ksort($objects);

        $pdf = "%PDF-1.4\n%\xE2\xE3\xCF\xD3\n";
        $offsets = [0];
        foreach ($objects as $id => $object) {
            $offsets[$id] = strlen($pdf);
            $pdf .= "{$id} 0 obj\n{$object}\nendobj\n";
        }

        $xrefOffset = strlen($pdf);
        $pdf .= "xref\n0 ".(count($objects) + 1)."\n0000000000 65535 f \n";
        foreach (array_keys($objects) as $id) {
            $pdf .= sprintf("%010d 00000 n \n", $offsets[$id]);
        }

        return $pdf."trailer\n<< /Size ".(count($objects) + 1)." /Root 1 0 R >>\nstartxref\n{$xrefOffset}\n%%EOF\n";
    }

    protected function generateExcelContent(string $csvContent): string
    {
        if (! class_exists(ZipArchive::class)) {
            throw new RuntimeException('PHP Zip extension is required to generate Excel reports.');
        }

        $sheetRows = '';
        foreach ($this->parseCsvRows($csvContent) as $rowIndex => $row) {
            $cells = '';
            foreach ($row as $value) {
                $escaped = htmlspecialchars($value, ENT_XML1 | ENT_QUOTES, 'UTF-8');
                $cells .= '<c t="inlineStr"><is><t xml:space="preserve">'.$escaped.'</t></is></c>';
            }
            $sheetRows .= '<row r="'.($rowIndex + 1).'">'.$cells.'</row>';
        }

        $temporaryPath = tempnam(sys_get_temp_dir(), 'qhsse-xlsx-');
        if ($temporaryPath === false) {
            throw new RuntimeException('Unable to allocate temporary Excel file.');
        }

        $zip = new ZipArchive;
        if ($zip->open($temporaryPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            @unlink($temporaryPath);
            throw new RuntimeException('Unable to create Excel archive.');
        }

        $zip->addFromString('[Content_Types].xml', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types"><Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/><Default Extension="xml" ContentType="application/xml"/><Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/><Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/></Types>');
        $zip->addFromString('_rels/.rels', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/></Relationships>');
        $zip->addFromString('xl/workbook.xml', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships"><sheets><sheet name="QHSSE Report" sheetId="1" r:id="rId1"/></sheets></workbook>');
        $zip->addFromString('xl/_rels/workbook.xml.rels', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/></Relationships>');
        $zip->addFromString('xl/worksheets/sheet1.xml', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"><sheetData>'.$sheetRows.'</sheetData></worksheet>');
        $zip->close();

        $content = file_get_contents($temporaryPath);
        @unlink($temporaryPath);

        if ($content === false) {
            throw new RuntimeException('Unable to read generated Excel archive.');
        }

        return $content;
    }

    /** @return list<list<string>> */
    protected function parseCsvRows(string $csvContent): array
    {
        $stream = fopen('php://temp', 'r+');
        if ($stream === false) {
            throw new RuntimeException('Unable to parse generated report data.');
        }

        fwrite($stream, $csvContent);
        rewind($stream);
        $rows = [];
        while (($row = fgetcsv($stream, escape: '')) !== false) {
            $rows[] = array_map(static fn ($value): string => (string) $value, $row);
        }
        fclose($stream);

        return $rows;
    }

    protected function sendCompletedNotification(): void
    {
        try {
            $notificationService = app(NotificationService::class);

            $notificationService->notify(
                recipient: $this->user,
                type: 'report.completed',
                context: [
                    'title' => "Laporan Selesai: {$this->report->name}",
                    'message' => "Laporan {$this->report->name} telah selesai di-generate. Format: {$this->report->format_label}. Anda dapat mengunduh laporan sekarang.",
                ],
                actor: $this->user,
                actionUrl: route('saved-reports.show', $this->report->id),
                moduleName: 'reporting',
                referenceId: $this->report->id,
                idempotencyKey: 'report.completed:'.$this->report->id,
            );

            Log::info("Completion notification sent for report ID: {$this->report->id}");
        } catch (\Exception $e) {
            Log::error("Failed to send completion notification for report ID: {$this->report->id}", [
                'error' => $e->getMessage(),
            ]);
        }
    }

    protected function sendFailedNotification(string $errorMessage): void
    {
        try {
            $notificationService = app(NotificationService::class);

            $notificationService->notify(
                recipient: $this->user,
                type: 'report.failed',
                context: [
                    'title' => "Laporan Gagal: {$this->report->name}",
                    'message' => "Laporan {$this->report->name} gagal di-generate. Error: {$errorMessage}. Silakan coba lagi atau hubungi administrator.",
                ],
                actor: $this->user,
                actionUrl: route('saved-reports.show', $this->report->id),
                moduleName: 'reporting',
                referenceId: $this->report->id,
                idempotencyKey: 'report.failed:'.$this->report->id,
            );

            Log::info("Failure notification sent for report ID: {$this->report->id}");
        } catch (\Exception $e) {
            Log::error("Failed to send failure notification for report ID: {$this->report->id}", [
                'error' => $e->getMessage(),
            ]);
        }
    }

    public function failed(\Throwable $exception): void
    {
        Log::error("GenerateReportJob failed permanently for report ID: {$this->report->id}", [
            'error' => $exception->getMessage(),
        ]);

        // Mark as failed
        $this->report->markAsFailed($exception->getMessage());

        // Send failure notification
        $this->sendFailedNotification($exception->getMessage());
    }

    // ============================================================================
    // REPORT TYPE GENERATORS
    // ============================================================================

    /**
     * Generate Incident Summary Report (CSV)
     */
    protected function generateIncidentSummaryCsv(array $params): string
    {
        $dateFrom = $params['date_from'] ?? null;
        $dateTo = $params['date_to'] ?? null;

        $query = IncidentReport::query()
            ->with(['severity', 'priority', 'site', 'area', 'department', 'reporter'])
            ->when($dateFrom, fn ($builder) => $builder->whereDate('occurred_at', '>=', $dateFrom))
            ->when($dateTo, fn ($builder) => $builder->whereDate('occurred_at', '<=', $dateTo))
            ->when($params['site_id'] ?? null, fn ($builder, $siteId) => $builder->where('site_id', $siteId))
            ->when($params['department_id'] ?? null, fn ($builder, $departmentId) => $builder->where('department_id', $departmentId))
            ->orderByDesc('occurred_at');

        $incidents = $query->get();

        $csv = "INCIDENT SUMMARY REPORT\n";
        $csv .= 'Generated: '.now()->format('Y-m-d H:i:s')."\n";
        $csv .= 'Period: '.($dateFrom ?? 'All').' to '.($dateTo ?? 'All')."\n";
        $csv .= 'Total Incidents: '.$incidents->count()."\n\n";
        $csv .= "Incident Number,Occurred At,Category,Title,Severity,Priority,Status,Site,Area,Department,Reporter,Description\n";

        foreach ($incidents as $incident) {
            $csv .= implode(',', array_map($this->escapeCsv(...), [
                $incident->incident_number,
                $incident->occurred_at?->format('Y-m-d H:i'),
                $incident->category,
                $incident->title,
                $incident->severity?->name,
                $incident->priority?->name,
                $incident->status,
                $incident->site?->name,
                $incident->area?->name,
                $incident->department?->name,
                $incident->reporter?->name,
                $incident->description,
            ]))."\n";
        }

        return $csv;
    }

    /**
     * Generate Safety Metrics Report (CSV)
     */
    protected function generateSafetyMetricsCsv(array $params): string
    {
        $dateFrom = $params['date_from'] ?? null;
        $dateTo = $params['date_to'] ?? null;
        $siteId = $params['site_id'] ?? null;
        $departmentId = $params['department_id'] ?? null;

        $incidents = IncidentReport::query()
            ->when($dateFrom, fn ($query) => $query->whereDate('occurred_at', '>=', $dateFrom))
            ->when($dateTo, fn ($query) => $query->whereDate('occurred_at', '<=', $dateTo))
            ->when($siteId, fn ($query) => $query->where('site_id', $siteId))
            ->when($departmentId, fn ($query) => $query->where('department_id', $departmentId));
        $capas = CapaAction::query()
            ->when($dateFrom, fn ($query) => $query->whereDate('created_at', '>=', $dateFrom))
            ->when($dateTo, fn ($query) => $query->whereDate('created_at', '<=', $dateTo))
            ->when($siteId, fn ($query) => $query->where('site_id', $siteId))
            ->when($departmentId, fn ($query) => $query->where('department_id', $departmentId));
        $inspections = Inspection::query()
            ->when($dateFrom, fn ($query) => $query->whereDate('scheduled_at', '>=', $dateFrom))
            ->when($dateTo, fn ($query) => $query->whereDate('scheduled_at', '<=', $dateTo))
            ->when($siteId, fn ($query) => $query->where('site_id', $siteId))
            ->when($departmentId, fn ($query) => $query->whereHas(
                'inspector.employee',
                fn ($employee) => $employee->where('department_id', $departmentId),
            ));
        $audits = Audit::query()
            ->when($dateFrom, fn ($query) => $query->whereDate('scheduled_date', '>=', $dateFrom))
            ->when($dateTo, fn ($query) => $query->whereDate('scheduled_date', '<=', $dateTo))
            ->when($siteId, fn ($query) => $query->whereHas(
                'department',
                fn ($department) => $department->where('site_id', $siteId),
            ))
            ->when($departmentId, fn ($query) => $query->where('department_id', $departmentId));
        $training = TrainingRecord::query()
            ->when($dateFrom, fn ($query) => $query->whereDate('start_date', '>=', $dateFrom))
            ->when($dateTo, fn ($query) => $query->whereDate('start_date', '<=', $dateTo))
            ->when($siteId || $departmentId, fn ($query) => $query->whereHas('employee', function ($employeeQuery) use ($siteId, $departmentId): void {
                $employeeQuery
                    ->when($siteId, fn ($builder) => $builder->where('site_id', $siteId))
                    ->when($departmentId, fn ($builder) => $builder->where('department_id', $departmentId));
            }));

        $csv = "QHSSE OVERVIEW REPORT\n";
        $csv .= 'Generated: '.now()->format('Y-m-d H:i:s')."\n";
        $csv .= 'Period: '.($dateFrom ?? 'All').' to '.($dateTo ?? 'All')."\n\n";
        $csv .= "Module,Total,Completed or Closed,Open or Pending\n";
        $csv .= 'Incidents,'.(clone $incidents)->count().','.(clone $incidents)->where('status', 'closed')->count().','.(clone $incidents)->whereNotIn('status', ['closed', 'rejected'])->count()."\n";
        $csv .= 'CAPA,'.(clone $capas)->count().','.(clone $capas)->where('status', 'closed')->count().','.(clone $capas)->whereNotIn('status', ['closed', 'rejected'])->count()."\n";
        $csv .= 'Inspections,'.(clone $inspections)->count().','.(clone $inspections)->where('status', 'completed')->count().','.(clone $inspections)->where('status', '!=', 'completed')->count()."\n";
        $csv .= 'Audits,'.(clone $audits)->count().','.(clone $audits)->where('status', 'closed')->count().','.(clone $audits)->where('status', '!=', 'closed')->count()."\n";
        $csv .= 'Training,'.(clone $training)->count().','.(clone $training)->where('status', 'completed')->count().','.(clone $training)->where('status', '!=', 'completed')->count()."\n";

        return $csv;
    }

    protected function generateInspectionSummaryCsv(array $params): string
    {
        $dateFrom = $params['date_from'] ?? null;
        $dateTo = $params['date_to'] ?? null;
        $query = Inspection::query()
            ->with(['template', 'site', 'area', 'inspector'])
            ->when($dateFrom, fn ($builder) => $builder->whereDate('scheduled_at', '>=', $dateFrom))
            ->when($dateTo, fn ($builder) => $builder->whereDate('scheduled_at', '<=', $dateTo))
            ->when($params['site_id'] ?? null, fn ($builder, $siteId) => $builder->where('site_id', $siteId))
            ->when($params['department_id'] ?? null, fn ($builder, $departmentId) => $builder->whereHas(
                'inspector.employee',
                fn ($employee) => $employee->where('department_id', $departmentId),
            ))
            ->orderByDesc('scheduled_at');

        $inspections = $query->get();
        $csv = "INSPECTION SUMMARY REPORT\n";
        $csv .= 'Generated: '.now()->format('Y-m-d H:i:s')."\n";
        $csv .= 'Period: '.($dateFrom ?? 'All').' to '.($dateTo ?? 'All')."\n";
        $csv .= 'Total Inspections: '.$inspections->count()."\n\n";
        $csv .= "Inspection Number,Template,Scheduled Date,Site,Area,Inspector,Status,Overall Result\n";
        foreach ($inspections as $inspection) {
            $csv .= implode(',', array_map($this->escapeCsv(...), [
                $inspection->inspection_number,
                $inspection->template?->name,
                $inspection->scheduled_at?->format('Y-m-d'),
                $inspection->site?->name,
                $inspection->area?->name,
                $inspection->inspector?->name,
                $inspection->status,
                $inspection->overall_result,
            ]))."\n";
        }

        return $csv;
    }

    /**
     * Escape CSV value
     */
    protected function escapeCsv(?string $value): string
    {
        if ($value === null) {
            return '';
        }

        // Neutralize spreadsheet formula injection
        if (preg_match('/^[=+\-@]/', $value) === 1) {
            $value = "'{$value}";
        }

        // Escape quotes and wrap in quotes if contains comma, quote, or newline
        if (str_contains($value, ',') || str_contains($value, '"') || str_contains($value, "\n")) {
            return '"'.str_replace('"', '""', $value).'"';
        }

        return $value;
    }

    /**
     * Generate Audit Findings Report (CSV)
     */
    protected function generateAuditFindingsCsv(array $params): string
    {
        $dateFrom = $params['date_from'] ?? null;
        $dateTo = $params['date_to'] ?? null;

        $audits = Audit::query()
            ->with(['department', 'leadAuditor', 'findings'])
            ->when($dateFrom, fn ($query) => $query->whereDate('scheduled_date', '>=', $dateFrom))
            ->when($dateTo, fn ($query) => $query->whereDate('scheduled_date', '<=', $dateTo))
            ->when($params['site_id'] ?? null, fn ($query, $siteId) => $query->whereHas(
                'department',
                fn ($department) => $department->where('site_id', $siteId),
            ))
            ->when($params['department_id'] ?? null, fn ($query, $departmentId) => $query->where('department_id', $departmentId))
            ->orderByDesc('scheduled_date')
            ->get();

        $csv = "AUDIT SUMMARY REPORT\n";
        $csv .= 'Generated: '.now()->format('Y-m-d H:i:s')."\n";
        $csv .= 'Period: '.($dateFrom ?? 'All').' to '.($dateTo ?? 'All')."\n";
        $csv .= 'Total Audits: '.$audits->count()."\n\n";
        $csv .= "Audit Number,Title,Type,Scheduled Date,Department,Lead Auditor,Status,Findings,Open Findings\n";
        foreach ($audits as $audit) {
            $csv .= implode(',', array_map($this->escapeCsv(...), [
                $audit->audit_number,
                $audit->title,
                $audit->audit_type,
                $audit->scheduled_date?->format('Y-m-d'),
                $audit->department?->name,
                $audit->leadAuditor?->name,
                $audit->status,
                $audit->findings->count(),
                $audit->findings->where('status', '!=', 'closed')->count(),
            ]))."\n";
        }

        return $csv;
    }

    /**
     * Generate Training Records Report (CSV)
     */
    protected function generateTrainingRecordsCsv(array $params): string
    {
        $dateFrom = $params['date_from'] ?? null;
        $dateTo = $params['date_to'] ?? null;

        $query = TrainingRecord::query()
            ->with(['employee.site', 'employee.department', 'trainingProgram'])
            ->when($dateFrom, fn ($builder) => $builder->whereDate('start_date', '>=', $dateFrom))
            ->when($dateTo, fn ($builder) => $builder->whereDate('start_date', '<=', $dateTo))
            ->when(($params['site_id'] ?? null) || ($params['department_id'] ?? null), function ($builder) use ($params): void {
                $builder->whereHas('employee', function ($employeeQuery) use ($params): void {
                    $employeeQuery
                        ->when($params['site_id'] ?? null, fn ($query, $siteId) => $query->where('site_id', $siteId))
                        ->when($params['department_id'] ?? null, fn ($query, $departmentId) => $query->where('department_id', $departmentId));
                });
            })
            ->orderByDesc('start_date');

        $records = $query->get();

        $csv = "TRAINING RECORDS REPORT\n";
        $csv .= 'Generated: '.now()->format('Y-m-d H:i:s')."\n";
        $csv .= 'Period: '.($dateFrom ?? 'All').' to '.($dateTo ?? 'All')."\n";
        $csv .= 'Total Records: '.$records->count()."\n\n";
        $csv .= "Employee Number,Employee,Site,Department,Program,Start Date,End Date,Provider,Status,Result,Certificate Number,Expiry Date,Score\n";
        foreach ($records as $record) {
            $csv .= implode(',', array_map($this->escapeCsv(...), [
                $record->employee?->employee_no,
                $record->employee?->name,
                $record->employee?->site?->name,
                $record->employee?->department?->name,
                $record->trainingProgram?->name,
                $record->start_date?->format('Y-m-d'),
                $record->end_date?->format('Y-m-d'),
                $record->provider,
                $record->status,
                $record->result,
                $record->certificate_number,
                $record->expiry_date?->format('Y-m-d'),
                $record->score,
            ]))."\n";
        }

        return $csv;
    }

    /**
     * Generate CAPA Effectiveness Report (CSV)
     */
    protected function generateCapaEffectivenessCsv(array $params): string
    {
        $dateFrom = $params['date_from'] ?? null;
        $dateTo = $params['date_to'] ?? null;

        $query = CapaAction::query()
            ->with(['assignedTo', 'priority', 'site', 'department'])
            ->when($dateFrom, fn ($builder) => $builder->whereDate('created_at', '>=', $dateFrom))
            ->when($dateTo, fn ($builder) => $builder->whereDate('created_at', '<=', $dateTo))
            ->when($params['site_id'] ?? null, fn ($builder, $siteId) => $builder->where('site_id', $siteId))
            ->when($params['department_id'] ?? null, fn ($builder, $departmentId) => $builder->where('department_id', $departmentId))
            ->orderBy('due_date');

        $actions = $query->get();

        // Calculate metrics
        $total = $actions->count();
        $completed = $actions->where('status', 'closed')->count();
        $overdue = $actions->filter(fn (CapaAction $action) => $action->is_overdue)->count();

        // Build CSV
        $csv = "CAPA EFFECTIVENESS REPORT\n";
        $csv .= 'Generated: '.now()->format('Y-m-d H:i:s')."\n";
        $csv .= 'Period: '.($dateFrom ?? 'All').' to '.($dateTo ?? 'All')."\n";
        $csv .= "\n";

        $csv .= "SUMMARY\n";
        $csv .= 'Total Actions,'.$total."\n";
        $csv .= 'Completed,'.$completed."\n";
        $csv .= 'Completion Rate,'.($total > 0 ? round(($completed / $total) * 100, 1) : 0)."%\n";
        $csv .= 'Overdue,'.$overdue."\n";
        $csv .= "\n";

        // Headers
        $csv .= "Action Number,Title,Source,Site,Department,Priority,Assigned To,Due Date,Status,Verification Note\n";

        // Data rows
        foreach ($actions as $action) {
            $csv .= $this->escapeCsv($action->action_number ?? '').',';
            $csv .= $this->escapeCsv($action->title ?? '').',';
            $csv .= $this->escapeCsv($action->source_module ?? '').',';
            $csv .= $this->escapeCsv($action->site?->name ?? '').',';
            $csv .= $this->escapeCsv($action->department?->name ?? '').',';
            $csv .= $this->escapeCsv($action->priority?->name ?? '').',';
            $csv .= $this->escapeCsv($action->assignedTo?->name ?? '').',';
            $csv .= $this->escapeCsv($action->due_date?->format('Y-m-d') ?? '').',';
            $csv .= $this->escapeCsv($action->status ?? '').',';
            $csv .= $this->escapeCsv($action->verification_note ?? '')."\n";
        }

        return $csv;
    }

    /**
     * Generate Custom Report (CSV)
     */
    protected function generateCustomCsv(array $params): string
    {
        $template = $this->report->template;

        $csv = "CUSTOM QHSSE REPORT\n";
        $csv .= 'Template: '.$template->name."\n";
        $csv .= 'Generated: '.now()->format('Y-m-d H:i:s')."\n";
        $csv .= 'Period: '.($params['date_from'] ?? 'All').' to '.($params['date_to'] ?? 'All')."\n\n";
        $csv .= "Section,Data Source,Enabled\n";
        foreach ($template->getSections() as $section) {
            $csv .= implode(',', array_map($this->escapeCsv(...), [
                $section['label'] ?? $section['key'] ?? 'Section',
                $section['data_source'] ?? '',
                ($section['enabled'] ?? true) ? 'Yes' : 'No',
            ]))."\n";
        }

        return $csv;
    }
}
