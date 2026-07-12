<?php

namespace App\Jobs\Modules\Reporting;

use App\Core\Activity\ActivityService;
use App\Core\Notification\NotificationService;
use App\Models\Modules\Reporting\SavedReport;
use App\Models\User;
use App\Models\Modules\Incident\IncidentReport;
use App\Models\Modules\Capa\CapaAction;
use App\Models\Modules\Inspection\Inspection;
use App\Models\Modules\Audit\Audit;
use App\Models\Modules\Audit\AuditFinding;
use App\Models\Modules\Training\TrainingRecord;
use App\Models\Modules\RiskManagement\RiskRegister;
use App\Models\Modules\LegalCompliance\LegalRequirement;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;

class GenerateReportJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 600; // 10 minutes
    public $tries = 1; // Don't retry automatically

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

            // Simulate report generation
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

        // Simulate processing time (remove in production)
        sleep(2);

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

        // Generate content based on format
        $content = match ($this->report->format) {
            'csv' => $this->generateCsvContent(),
            'pdf' => $this->generatePdfContent(),
            'excel' => $this->generateExcelContent(),
            default => 'Unknown format',
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
            'safety_metrics' => $this->generateSafetyMetricsCsv($params),
            'compliance_status' => $this->generateComplianceStatusCsv($params),
            'audit_findings' => $this->generateAuditFindingsCsv($params),
            'risk_assessment' => $this->generateRiskAssessmentCsv($params),
            'training_records' => $this->generateTrainingRecordsCsv($params),
            'capa_effectiveness' => $this->generateCapaEffectivenessCsv($params),
            'custom' => $this->generateCustomCsv($params),
            default => "Report type not implemented: {$template->type}\n",
        };
    }

    protected function generatePdfContent(): string
    {
        // STUB: For now, return text content
        // In production, use Dompdf or similar
        $params = $this->report->parameters;
        $template = $this->report->template;

        return "QHSSE REPORT\n\n" .
               "Template: {$template->name}\n" .
               "Generated: " . now()->format('Y-m-d H:i:s') . "\n" .
               "Period: {$params['date_from']} to {$params['date_to']}\n\n" .
               "Note: This is a stub implementation. PDF generation will be implemented with Dompdf.\n\n" .
               "Sample report content...";
    }

    protected function generateExcelContent(): string
    {
        // STUB: For now, return CSV-like content
        // In production, use Laravel Excel (Maatwebsite/Laravel-Excel)
        return $this->generateCsvContent();
    }

    protected function sendCompletedNotification(): void
    {
        try {
            $notificationService = app(NotificationService::class);

            $notificationService->notify(
                users: [$this->user],
                type: 'report.completed',
                title: "Laporan Selesai: {$this->report->name}",
                message: "Laporan {$this->report->name} telah selesai di-generate. Format: {$this->report->format_label}. Anda dapat mengunduh laporan sekarang.",
                actionUrl: route('saved-reports.show', $this->report->id),
                moduleName: 'reporting',
                referenceId: $this->report->id,
                referenceType: SavedReport::class
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
                users: [$this->user],
                type: 'report.failed',
                title: "Laporan Gagal: {$this->report->name}",
                message: "Laporan {$this->report->name} gagal di-generate. Error: {$errorMessage}. Silakan coba lagi atau hubungi administrator.",
                actionUrl: route('saved-reports.show', $this->report->id),
                moduleName: 'reporting',
                referenceId: $this->report->id,
                referenceType: SavedReport::class
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

        // Query incidents within date range
        $query = IncidentReport::query()
            ->with(['severity', 'priority', 'status', 'site', 'area', 'reporter', 'investigator'])
            ->orderBy('incident_date', 'desc');

        if ($dateFrom) {
            $query->where('incident_date', '>=', $dateFrom);
        }
        if ($dateTo) {
            $query->where('incident_date', '<=', $dateTo);
        }

        $incidents = $query->get();

        // Build CSV
        $csv = "INCIDENT SUMMARY REPORT\n";
        $csv .= "Generated: " . now()->format('Y-m-d H:i:s') . "\n";
        $csv .= "Period: " . ($dateFrom ?? 'All') . " to " . ($dateTo ?? 'All') . "\n";
        $csv .= "Total Incidents: " . $incidents->count() . "\n";
        $csv .= "\n";

        // Headers
        $csv .= "Report Number,Incident Date,Type,Title,Severity,Priority,Status,Site,Area,Reporter,Investigator,Description\n";

        // Data rows
        foreach ($incidents as $incident) {
            $csv .= $this->escapeCsv($incident->report_number) . ",";
            $csv .= $this->escapeCsv($incident->incident_date?->format('Y-m-d') ?? '') . ",";
            $csv .= $this->escapeCsv($incident->type_label ?? '') . ",";
            $csv .= $this->escapeCsv($incident->title ?? '') . ",";
            $csv .= $this->escapeCsv($incident->severity?->name ?? '') . ",";
            $csv .= $this->escapeCsv($incident->priority?->name ?? '') . ",";
            $csv .= $this->escapeCsv($incident->status?->name ?? '') . ",";
            $csv .= $this->escapeCsv($incident->site?->name ?? '') . ",";
            $csv .= $this->escapeCsv($incident->area?->name ?? '') . ",";
            $csv .= $this->escapeCsv($incident->reporter?->name ?? '') . ",";
            $csv .= $this->escapeCsv($incident->investigator?->name ?? '') . ",";
            $csv .= $this->escapeCsv($incident->description ?? '') . "\n";
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

        // Get incidents within period
        $incidentsQuery = IncidentReport::query();
        if ($dateFrom) {
            $incidentsQuery->where('incident_date', '>=', $dateFrom);
        }
        if ($dateTo) {
            $incidentsQuery->where('incident_date', '<=', $dateTo);
        }

        $totalIncidents = $incidentsQuery->count();
        $incidentsBySeverity = $incidentsQuery->select('severity_id', DB::raw('count(*) as count'))
            ->groupBy('severity_id')
            ->with('severity')
            ->get();

        $incidentsByType = $incidentsQuery->select('type', DB::raw('count(*) as count'))
            ->groupBy('type')
            ->get();

        // Get inspections
        $inspectionsQuery = Inspection::query();
        if ($dateFrom) {
            $inspectionsQuery->where('scheduled_date', '>=', $dateFrom);
        }
        if ($dateTo) {
            $inspectionsQuery->where('scheduled_date', '<=', $dateTo);
        }
        $totalInspections = $inspectionsQuery->count();
        $completedInspections = $inspectionsQuery->whereIn('status', ['completed'])->count();

        // Build CSV
        $csv = "SAFETY METRICS REPORT\n";
        $csv .= "Generated: " . now()->format('Y-m-d H:i:s') . "\n";
        $csv .= "Period: " . ($dateFrom ?? 'All') . " to " . ($dateTo ?? 'All') . "\n";
        $csv .= "\n";

        $csv .= "INCIDENT SUMMARY\n";
        $csv .= "Total Incidents," . $totalIncidents . "\n";
        $csv .= "\n";

        $csv .= "Incidents by Severity\n";
        $csv .= "Severity,Count\n";
        foreach ($incidentsBySeverity as $row) {
            $csv .= $this->escapeCsv($row->severity?->name ?? 'Unknown') . "," . $row->count . "\n";
        }
        $csv .= "\n";

        $csv .= "Incidents by Type\n";
        $csv .= "Type,Count\n";
        foreach ($incidentsByType as $row) {
            $csv .= $this->escapeCsv($row->type ?? 'Unknown') . "," . $row->count . "\n";
        }
        $csv .= "\n";

        $csv .= "INSPECTION SUMMARY\n";
        $csv .= "Total Inspections," . $totalInspections . "\n";
        $csv .= "Completed Inspections," . $completedInspections . "\n";
        $csv .= "Completion Rate," . ($totalInspections > 0 ? round(($completedInspections / $totalInspections) * 100, 1) : 0) . "%\n";

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

        // Escape quotes and wrap in quotes if contains comma, quote, or newline
        if (str_contains($value, ',') || str_contains($value, '"') || str_contains($value, "\n")) {
            return '"' . str_replace('"', '""', $value) . '"';
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

        // Query audit findings
        $query = AuditFinding::query()
            ->with(['audit', 'audit.site', 'severity', 'status', 'assignedTo'])
            ->orderBy('created_at', 'desc');

        if ($dateFrom) {
            $query->whereHas('audit', function ($q) use ($dateFrom) {
                $q->where('audit_date', '>=', $dateFrom);
            });
        }
        if ($dateTo) {
            $query->whereHas('audit', function ($q) use ($dateTo) {
                $q->where('audit_date', '<=', $dateTo);
            });
        }

        $findings = $query->get();

        // Build CSV
        $csv = "AUDIT FINDINGS REPORT\n";
        $csv .= "Generated: " . now()->format('Y-m-d H:i:s') . "\n";
        $csv .= "Period: " . ($dateFrom ?? 'All') . " to " . ($dateTo ?? 'All') . "\n";
        $csv .= "Total Findings: " . $findings->count() . "\n";
        $csv .= "\n";

        // Headers
        $csv .= "Finding Number,Audit Title,Audit Date,Site,Finding Description,Severity,Status,Assigned To,Due Date,Root Cause,Corrective Action\n";

        // Data rows
        foreach ($findings as $finding) {
            $csv .= $this->escapeCsv($finding->finding_number ?? '') . ",";
            $csv .= $this->escapeCsv($finding->audit?->title ?? '') . ",";
            $csv .= $this->escapeCsv($finding->audit?->audit_date?->format('Y-m-d') ?? '') . ",";
            $csv .= $this->escapeCsv($finding->audit?->site?->name ?? '') . ",";
            $csv .= $this->escapeCsv($finding->description ?? '') . ",";
            $csv .= $this->escapeCsv($finding->severity?->name ?? '') . ",";
            $csv .= $this->escapeCsv($finding->status?->name ?? '') . ",";
            $csv .= $this->escapeCsv($finding->assignedTo?->name ?? '') . ",";
            $csv .= $this->escapeCsv($finding->due_date?->format('Y-m-d') ?? '') . ",";
            $csv .= $this->escapeCsv($finding->root_cause ?? '') . ",";
            $csv .= $this->escapeCsv($finding->corrective_action ?? '') . "\n";
        }

        return $csv;
    }

    /**
     * Generate Risk Assessment Report (CSV)
     */
    protected function generateRiskAssessmentCsv(array $params): string
    {
        $dateFrom = $params['date_from'] ?? null;
        $dateTo = $params['date_to'] ?? null;

        // Query risk registers
        $query = RiskRegister::query()
            ->with(['site', 'department', 'owner', 'riskLevel'])
            ->orderBy('risk_score', 'desc');

        if ($dateFrom) {
            $query->where('created_at', '>=', $dateFrom);
        }
        if ($dateTo) {
            $query->where('created_at', '<=', $dateTo);
        }

        $risks = $query->get();

        // Build CSV
        $csv = "RISK ASSESSMENT REPORT\n";
        $csv .= "Generated: " . now()->format('Y-m-d H:i:s') . "\n";
        $csv .= "Period: " . ($dateFrom ?? 'All') . " to " . ($dateTo ?? 'All') . "\n";
        $csv .= "Total Risks: " . $risks->count() . "\n";
        $csv .= "\n";

        // Headers
        $csv .= "Risk ID,Title,Site,Department,Owner,Likelihood,Consequence,Risk Score,Risk Level,Control Measures,Status\n";

        // Data rows
        foreach ($risks as $risk) {
            $csv .= $this->escapeCsv($risk->risk_id ?? '') . ",";
            $csv .= $this->escapeCsv($risk->title ?? '') . ",";
            $csv .= $this->escapeCsv($risk->site?->name ?? '') . ",";
            $csv .= $this->escapeCsv($risk->department?->name ?? '') . ",";
            $csv .= $this->escapeCsv($risk->owner?->name ?? '') . ",";
            $csv .= $this->escapeCsv($risk->likelihood ?? '') . ",";
            $csv .= $this->escapeCsv($risk->consequence ?? '') . ",";
            $csv .= $this->escapeCsv($risk->risk_score ?? '') . ",";
            $csv .= $this->escapeCsv($risk->riskLevel?->level ?? '') . ",";
            $csv .= $this->escapeCsv($risk->control_measures ?? '') . ",";
            $csv .= $this->escapeCsv($risk->status ?? '') . "\n";
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

        // Query training records
        $query = TrainingRecord::query()
            ->with(['employee', 'program', 'trainer'])
            ->orderBy('training_date', 'desc');

        if ($dateFrom) {
            $query->where('training_date', '>=', $dateFrom);
        }
        if ($dateTo) {
            $query->where('training_date', '<=', $dateTo);
        }

        $records = $query->get();

        // Build CSV
        $csv = "TRAINING RECORDS REPORT\n";
        $csv .= "Generated: " . now()->format('Y-m-d H:i:s') . "\n";
        $csv .= "Period: " . ($dateFrom ?? 'All') . " to " . ($dateTo ?? 'All') . "\n";
        $csv .= "Total Records: " . $records->count() . "\n";
        $csv .= "\n";

        // Headers
        $csv .= "Employee,Program,Training Date,Duration (Hours),Trainer,Status,Certificate Number,Expiry Date,Score\n";

        // Data rows
        foreach ($records as $record) {
            $csv .= $this->escapeCsv($record->employee?->name ?? '') . ",";
            $csv .= $this->escapeCsv($record->program?->name ?? '') . ",";
            $csv .= $this->escapeCsv($record->training_date?->format('Y-m-d') ?? '') . ",";
            $csv .= $this->escapeCsv($record->duration_hours ?? '') . ",";
            $csv .= $this->escapeCsv($record->trainer?->name ?? '') . ",";
            $csv .= $this->escapeCsv($record->status ?? '') . ",";
            $csv .= $this->escapeCsv($record->certificate_number ?? '') . ",";
            $csv .= $this->escapeCsv($record->expiry_date?->format('Y-m-d') ?? '') . ",";
            $csv .= $this->escapeCsv($record->score ?? '') . "\n";
        }

        return $csv;
    }

    /**
     * Generate Compliance Status Report (CSV)
     */
    protected function generateComplianceStatusCsv(array $params): string
    {
        $dateFrom = $params['date_from'] ?? null;
        $dateTo = $params['date_to'] ?? null;

        // Query legal requirements if model exists
        $csv = "COMPLIANCE STATUS REPORT\n";
        $csv .= "Generated: " . now()->format('Y-m-d H:i:s') . "\n";
        $csv .= "Period: " . ($dateFrom ?? 'All') . " to " . ($dateTo ?? 'All') . "\n";
        $csv .= "\n";

        // Check if LegalRequirement model is available
        if (class_exists(LegalRequirement::class)) {
            $query = LegalRequirement::query()
                ->orderBy('due_date', 'asc');

            if ($dateFrom) {
                $query->where('created_at', '>=', $dateFrom);
            }
            if ($dateTo) {
                $query->where('created_at', '<=', $dateTo);
            }

            $requirements = $query->get();

            $csv .= "Total Requirements: " . $requirements->count() . "\n\n";
            $csv .= "Requirement,Category,Status,Due Date,Responsible,Compliance Rate\n";

            foreach ($requirements as $req) {
                $csv .= $this->escapeCsv($req->title ?? '') . ",";
                $csv .= $this->escapeCsv($req->category ?? '') . ",";
                $csv .= $this->escapeCsv($req->status ?? '') . ",";
                $csv .= $this->escapeCsv($req->due_date?->format('Y-m-d') ?? '') . ",";
                $csv .= $this->escapeCsv($req->responsible?->name ?? '') . ",";
                $csv .= $this->escapeCsv($req->compliance_rate ?? '0') . "%\n";
            }
        } else {
            $csv .= "Legal Compliance module not yet implemented.\n";
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

        // Query CAPA actions
        $query = CapaAction::query()
            ->with(['assignedTo', 'priority', 'status'])
            ->orderBy('due_date', 'asc');

        if ($dateFrom) {
            $query->where('created_at', '>=', $dateFrom);
        }
        if ($dateTo) {
            $query->where('created_at', '<=', $dateTo);
        }

        $actions = $query->get();

        // Calculate metrics
        $total = $actions->count();
        $completed = $actions->whereIn('status.code', ['completed', 'verified'])->count();
        $overdue = $actions->where('due_date', '<', now())->whereNotIn('status.code', ['completed', 'verified'])->count();

        // Build CSV
        $csv = "CAPA EFFECTIVENESS REPORT\n";
        $csv .= "Generated: " . now()->format('Y-m-d H:i:s') . "\n";
        $csv .= "Period: " . ($dateFrom ?? 'All') . " to " . ($dateTo ?? 'All') . "\n";
        $csv .= "\n";

        $csv .= "SUMMARY\n";
        $csv .= "Total Actions," . $total . "\n";
        $csv .= "Completed," . $completed . "\n";
        $csv .= "Completion Rate," . ($total > 0 ? round(($completed / $total) * 100, 1) : 0) . "%\n";
        $csv .= "Overdue," . $overdue . "\n";
        $csv .= "\n";

        // Headers
        $csv .= "Action Number,Title,Type,Priority,Assigned To,Due Date,Status,Effectiveness Rating\n";

        // Data rows
        foreach ($actions as $action) {
            $csv .= $this->escapeCsv($action->action_number ?? '') . ",";
            $csv .= $this->escapeCsv($action->title ?? '') . ",";
            $csv .= $this->escapeCsv($action->type ?? '') . ",";
            $csv .= $this->escapeCsv($action->priority?->name ?? '') . ",";
            $csv .= $this->escapeCsv($action->assignedTo?->name ?? '') . ",";
            $csv .= $this->escapeCsv($action->due_date?->format('Y-m-d') ?? '') . ",";
            $csv .= $this->escapeCsv($action->status?->name ?? '') . ",";
            $csv .= $this->escapeCsv($action->effectiveness_rating ?? 'N/A') . "\n";
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
        $csv .= "Template: " . $template->name . "\n";
        $csv .= "Generated: " . now()->format('Y-m-d H:i:s') . "\n";
        $csv .= "Period: " . ($params['date_from'] ?? 'All') . " to " . ($params['date_to'] ?? 'All') . "\n";
        $csv .= "\n";
        $csv .= "Note: Custom report generation requires template-specific configuration.\n";
        $csv .= "This is a placeholder. Implement custom logic based on template config.\n";

        return $csv;
    }
}
