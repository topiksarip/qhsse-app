# Workflow — Reporting & Export

## 1. No Workflow — Reports Are Generated On Demand

Modul Reporting & Export **tidak menggunakan workflow engine**. Reports di-generate **on demand** oleh user melalui halaman Configure, diproses secara asynchronous oleh queued job, dan hasilnya disimpan sebagai file yang dapat di-download.

Tidak ada status lifecycle yang melibatkan `WorkflowService`, `workflow_instances`, atau `workflow_histories`.

## 2. Report Generation Lifecycle

Meskipun tidak ada workflow engine, report generation memiliki **lifecycle status** sendiri di kolom `saved_reports.status`:

```
                          User submit generate
                                │
                                ▼
                         ┌─────────────┐
                         │   pending    │
                         │ (Menunggu)   │
                         └──────┬──────┘
                                │
                          Job diproses
                          oleh worker
                                │
                                ▼
                         ┌─────────────┐
                         │  processing  │
                         │(Sedang Diproses)│
                         └──────┬──────┘
                                │
                    ┌───────────┼───────────┐
                    │           │           │
                    ▼           │           ▼
             ┌──────────┐      │     ┌──────────┐
             │ completed │      │     │  failed   │
             │ (Selesai) │      │     │ (Gagal)   │
             └─────┬────┘      │     └─────┬────┘
                   │           │           │
                   ▼           │           ▼
             File siap        │     User dapat
             di-download      │     re-generate
                              │
                              ▼
                       (Status tetap
                        tidak berubah
                        setelah terminal)
```

### Status Definitions

| Status | Type | Description |
|---|---|---|
| `pending` | Initial | Record dibuat, job belum mulai diproses oleh worker. |
| `processing` | Active | Worker sedang meng-agregasi data dan generate file. |
| `completed` | **Terminal** | File berhasil di-generate, siap di-download. `file_path` diisi. |
| `failed` | **Terminal** | Job gagal. Error disimpan di `parameters.error`. User dapat re-generate. |

## 3. Simplified Linear View

```
pending ──(job picked up)──→ processing ──(success)──→ completed (file ready)
                                 │
                                 └──(exception)──→ failed (error stored)
```

### State Rules

1. **`pending`** — Initial state. `saved_reports` record dibuat, `GenerateReportJob` di-dispatch. `file_path` = NULL. User melihat status "Menunggu" di list.
2. **`processing`** — Worker memproses job. Status berubah dari `pending` ke `processing` saat job dimulai. `file_path` tetap NULL.
3. **`completed`** — Terminal. File berhasil di-generate dan disimpan. `file_path` diisi dengan path file. Notifikasi `report.completed` dikirim. User dapat download.
4. **`failed`** — Terminal. Job melempar exception. `file_path` = NULL. Error message disimpan di `parameters.error`. Notifikasi `report.failed` dikirim. User dapat re-generate (membuat record baru).

## 4. No WorkflowService Integration

Modul reporting **tidak memanggil** `WorkflowService::start()`, `WorkflowService::transition()`, atau metode workflow lainnya.

```php
// ❌ TIDAK digunakan di modul reporting:
// $this->workflowService->start('reporting', $report->id, $actor);
// $this->workflowService->transition('reporting', $report->id, 'generate', $actor);

// ✅ Yang digunakan: direct status update pada model
$report->update(['status' => SavedReport::STATUS_PROCESSING]);
$report->update(['status' => SavedReport::STATUS_COMPLETED, 'file_path' => $filePath]);
$report->update(['status' => SavedReport::STATUS_FAILED, 'parameters' => $parametersWithError]);
```

## 5. Controller Integration

### Generate Report (store)

```php
public function store(GenerateReportRequest $request): RedirectResponse
{
    $this->authorize('generate', SavedReport::class);

    // Apply data scope to parameters
    $parameters = $this->applyDataScope($request->parameters, auth()->user());

    // Create saved_report with pending status
    $report = SavedReport::create([
        'report_template_id' => $request->report_template_id,
        'name'              => $request->name,
        'parameters'        => $parameters,
        'generated_by'      => auth()->id(),
        'generated_at'      => now(),
        'format'            => $parameters['format'],
        'status'            => SavedReport::STATUS_PENDING,
        'file_path'         => null,
    ]);

    // Dispatch async job
    GenerateReportJob::dispatch($report->id);

    // Log
    ActivityService::log('reporting', $report->id, 'report.generated',
        'Laporan dibuat: ' . $report->name, auth()->user());

    AuditService::created($report, auth()->user(), 'reporting', $report->id);

    return redirect()
        ->route('reporting.reports.show', $report)
        ->with('success', 'Laporan sedang di-generate. Anda akan dinotifikasi setelah selesai.');
}
```

### Download Report

```php
public function download(SavedReport $report): Response
{
    $this->authorize('download', $report);

    if (!$report->isDownloadable()) {
        return back()->withErrors([
            'download' => 'Laporan belum siap untuk diunduh atau gagal di-generate.'
        ]);
    }

    $file = Storage::disk('local')->get($report->file_path);

    $mimeType = match($report->format) {
        'csv'   => 'text/csv',
        'pdf'   => 'application/pdf',
        'excel' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
    };

    $extension = $report->format === 'excel' ? 'xlsx' : $report->format;
    $filename = Str::slug($report->name) . '.' . $extension;

    ActivityService::log('reporting', $report->id, 'report.downloaded',
        'Laporan diunduh', auth()->user());

    AuditService::log('report.downloaded', $report, null, null,
        auth()->user(), 'reporting', $report->id);

    return response($file, 200, [
        'Content-Type'        => $mimeType,
        'Content-Disposition' => 'attachment; filename="' . $filename . '"',
    ]);
}
```

## 6. Job Processing

### GenerateReportJob

```php
class GenerateReportJob implements ShouldQueue
{
    public int $tries = 1;     // No retry — fail and notify immediately
    public int $timeout = 300; // 5 minutes max

    public function handle(ReportGeneratorService $generator): void
    {
        $report = SavedReport::findOrFail($this->savedReportId);

        // Transition: pending → processing
        $report->update(['status' => SavedReport::STATUS_PROCESSING]);

        try {
            // Generate file (aggregates data from all modules)
            $filePath = $generator->generate($report);

            // Transition: processing → completed
            $report->update([
                'status'    => SavedReport::STATUS_COMPLETED,
                'file_path' => $filePath,
            ]);

            // Notifications
            NotificationService::notify(
                $report->generatedBy,
                'report.completed',
                [
                    'report_name' => $report->name,
                    'format'      => $report->format,
                ],
                null,
                'reporting',
                $report->id,
                route('reporting.reports.show', $report->id),
            );

            // Audit
            ActivityService::log('reporting', $report->id, 'report.completed',
                'Laporan selesai: ' . $report->name, $report->generatedBy);

        } catch (\Exception $e) {
            // Transition: processing → failed
            $parameters = $report->parameters;
            $parameters['error'] = $e->getMessage();

            $report->update([
                'status'     => SavedReport::STATUS_FAILED,
                'parameters' => $parameters,
                'file_path'  => null,
            ]);

            // Notifications
            NotificationService::notify(
                $report->generatedBy,
                'report.failed',
                [
                    'report_name'   => $report->name,
                    'error_message'  => $e->getMessage(),
                ],
                null,
                'reporting',
                $report->id,
                route('reporting.reports.show', $report->id),
            );

            // Audit
            ActivityService::log('reporting', $report->id, 'report.failed',
                'Laporan gagal: ' . $report->name, $report->generatedBy,
                ['error' => $e->getMessage()]);
        }
    }
}
```

## 7. Audit Trail

Semua aktivitas dicatat via `audit_logs` dan `activity_logs` dengan `module_name = 'reporting'`:

| Event | Trigger | reference_id |
|---|---|---|
| `created` | Template dibuat | `report_templates.id` |
| `updated` | Template di-update | `report_templates.id` |
| `report.generated` | Report generate diminta | `saved_reports.id` |
| `report.completed` | Job selesai sukses | `saved_reports.id` |
| `report.failed` | Job gagal | `saved_reports.id` |
| `report.downloaded` | User download file | `saved_reports.id` |
| `report.deleted` | Saved report dihapus | `saved_reports.id` |

## 8. Terminal Status Rules

- **`completed`** adalah terminal. Tidak ada perubahan status lebih lanjut. File tersedia untuk download selamanya (selama record tidak dihapus).
- **`failed`** adalah terminal tetapi user dapat **re-generate** — ini membuat record `saved_reports` baru, bukan mengubah record yang failed.
- Tidak ada kemampuan untuk "retry" record yang failed. Re-generate = record baru.
- Tidak ada kemampuan untuk membatalkan (cancel) report yang sedang `pending` atau `processing` di Phase 5. (Defer ke fase mendatang jika diperlukan.)

## 9. Summary

| Aspect | Value |
|---|---|
| Workflow Engine | ❌ Not used |
| WorkflowService | ❌ Not called |
| NumberingService | ❌ Not used |
| Status Management | ✅ Direct model update |
| Async Processing | ✅ Laravel Queue (GenerateReportJob) |
| Terminal States | `completed`, `failed` |
| Re-generate | ✅ Creates new record |
| Cancel | ❌ Not in Phase 5 |
| Scheduled Reports | ❌ Not in Phase 5 |
