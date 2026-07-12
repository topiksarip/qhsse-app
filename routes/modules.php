<?php

use App\Http\Controllers\Modules\Audit\AuditController;
use App\Http\Controllers\Modules\DocumentControl\DocumentControlController;
use App\Http\Controllers\Modules\Incident\IncidentReportController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified'])->prefix('modules')->name('modules.')->group(function (): void {
    Route::get('/health', fn () => response()->json([
        'status' => 'ok',
        'scope' => 'module-registry',
    ]))->name('health');
});

// Incident Reporting Module
Route::middleware(['auth', 'verified'])
    ->prefix('incident-reports')
    ->name('incident.reports.')
    ->group(function (): void {
        Route::get('/', [IncidentReportController::class, 'index'])
            ->name('index')
            ->middleware('permission:incident.reports.view');

        Route::get('/create', [IncidentReportController::class, 'create'])
            ->name('create')
            ->middleware('permission:incident.reports.create');

        Route::post('/', [IncidentReportController::class, 'store'])
            ->name('store')
            ->middleware('permission:incident.reports.create');

        Route::get('/export', [IncidentReportController::class, 'export'])
            ->name('export')
            ->middleware('permission:incident.reports.export');

        Route::get('/{incidentReport}', [IncidentReportController::class, 'show'])
            ->name('show')
            ->middleware('permission:incident.reports.view');

        Route::get('/{incidentReport}/edit', [IncidentReportController::class, 'edit'])
            ->name('edit')
            ->middleware('permission:incident.reports.update');

        Route::put('/{incidentReport}', [IncidentReportController::class, 'update'])
            ->name('update')
            ->middleware('permission:incident.reports.update');

        Route::post('/{incidentReport}/submit', [IncidentReportController::class, 'submit'])
            ->name('submit')
            ->middleware('permission:incident.reports.submit');

        Route::post('/{incidentReport}/review', [IncidentReportController::class, 'review'])
            ->name('review')
            ->middleware('permission:incident.reports.review');

        Route::post('/{incidentReport}/close', [IncidentReportController::class, 'close'])
            ->name('close')
            ->middleware('permission:incident.reports.close');
    });

// Investigation & RCA Module
Route::middleware(['auth', 'verified'])
    ->prefix('investigations')
    ->name('investigation.reports.')
    ->group(function (): void {
        Route::get('/', [\App\Http\Controllers\Modules\Investigation\InvestigationController::class, 'index'])
            ->name('index')->middleware('permission:investigation.reports.view');

        Route::get('/create', [\App\Http\Controllers\Modules\Investigation\InvestigationController::class, 'create'])
            ->name('create')->middleware('permission:investigation.reports.create');

        Route::post('/', [\App\Http\Controllers\Modules\Investigation\InvestigationController::class, 'store'])
            ->name('store')->middleware('permission:investigation.reports.create');

        Route::get('/export', [\App\Http\Controllers\Modules\Investigation\InvestigationController::class, 'export'])
            ->name('export')->middleware('permission:investigation.reports.export');

        Route::get('/{investigation}', [\App\Http\Controllers\Modules\Investigation\InvestigationController::class, 'show'])
            ->name('show')->middleware('permission:investigation.reports.view');

        Route::get('/{investigation}/edit', [\App\Http\Controllers\Modules\Investigation\InvestigationController::class, 'edit'])
            ->name('edit')->middleware('permission:investigation.reports.update');

        Route::put('/{investigation}', [\App\Http\Controllers\Modules\Investigation\InvestigationController::class, 'update'])
            ->name('update')->middleware('permission:investigation.reports.update');

        Route::post('/{investigation}/start', [\App\Http\Controllers\Modules\Investigation\InvestigationController::class, 'start'])
            ->name('start')->middleware('permission:investigation.reports.submit');

        Route::post('/{investigation}/complete', [\App\Http\Controllers\Modules\Investigation\InvestigationController::class, 'complete'])
            ->name('complete')->middleware('permission:investigation.reports.close');

        Route::post('/{investigation}/cancel', [\App\Http\Controllers\Modules\Investigation\InvestigationController::class, 'cancel'])
            ->name('cancel')->middleware('permission:investigation.reports.update');
    });

// CAPA / Action Tracking Module
Route::middleware(['auth', 'verified'])
    ->prefix('capa-actions')
    ->name('capa.actions.')
    ->group(function (): void {
        Route::get('/', [\App\Http\Controllers\Modules\Capa\CapaActionController::class, 'index'])->name('index')->middleware('permission:capa.actions.view');
        Route::get('/create', [\App\Http\Controllers\Modules\Capa\CapaActionController::class, 'create'])->name('create')->middleware('permission:capa.actions.create');
        Route::post('/', [\App\Http\Controllers\Modules\Capa\CapaActionController::class, 'store'])->name('store')->middleware('permission:capa.actions.create');
        Route::get('/export', [\App\Http\Controllers\Modules\Capa\CapaActionController::class, 'export'])->name('export')->middleware('permission:capa.actions.export');
        Route::get('/{capaAction}', [\App\Http\Controllers\Modules\Capa\CapaActionController::class, 'show'])->name('show')->middleware('permission:capa.actions.view');
        Route::get('/{capaAction}/edit', [\App\Http\Controllers\Modules\Capa\CapaActionController::class, 'edit'])->name('edit')->middleware('permission:capa.actions.update');
        Route::put('/{capaAction}', [\App\Http\Controllers\Modules\Capa\CapaActionController::class, 'update'])->name('update')->middleware('permission:capa.actions.update');
        Route::post('/{capaAction}/start', [\App\Http\Controllers\Modules\Capa\CapaActionController::class, 'start'])->name('start')->middleware('permission:capa.actions.update');
        Route::post('/{capaAction}/submit-verification', [\App\Http\Controllers\Modules\Capa\CapaActionController::class, 'submitVerification'])->name('submit_verification')->middleware('permission:capa.actions.submit');
        Route::post('/{capaAction}/verify-close', [\App\Http\Controllers\Modules\Capa\CapaActionController::class, 'verifyClose'])->name('verify_close')->middleware('permission:capa.actions.close');
        Route::post('/{capaAction}/reject', [\App\Http\Controllers\Modules\Capa\CapaActionController::class, 'reject'])->name('reject')->middleware('permission:capa.actions.reject');
        Route::post('/{capaAction}/restart', [\App\Http\Controllers\Modules\Capa\CapaActionController::class, 'restart'])->name('restart')->middleware('permission:capa.actions.update');
    });

// Inspection Checklist Module — Templates
Route::middleware(['auth', 'verified'])
    ->prefix('inspection-templates')
    ->name('inspection.templates.')
    ->group(function (): void {
        Route::get('/', [App\Http\Controllers\Modules\Inspection\InspectionController::class, 'templateIndex'])->name('index')->middleware('permission:inspection.checklists.view');
        Route::get('/create', [App\Http\Controllers\Modules\Inspection\InspectionController::class, 'templateCreate'])->name('create')->middleware('permission:inspection.checklists.create');
        Route::post('/', [App\Http\Controllers\Modules\Inspection\InspectionController::class, 'templateStore'])->name('store')->middleware('permission:inspection.checklists.create');
        Route::get('/{template}', [App\Http\Controllers\Modules\Inspection\InspectionController::class, 'templateShow'])->name('show')->middleware('permission:inspection.checklists.view');
        Route::get('/{template}/edit', [App\Http\Controllers\Modules\Inspection\InspectionController::class, 'templateEdit'])->name('edit')->middleware('permission:inspection.checklists.update');
        Route::put('/{template}', [App\Http\Controllers\Modules\Inspection\InspectionController::class, 'templateUpdate'])->name('update')->middleware('permission:inspection.checklists.update');
        Route::delete('/{template}', [App\Http\Controllers\Modules\Inspection\InspectionController::class, 'templateDestroy'])->name('destroy')->middleware('permission:inspection.checklists.update');
    });

// Inspection Checklist Module — Inspections
Route::middleware(['auth', 'verified'])
    ->prefix('inspections')
    ->name('inspection.checklists.')
    ->group(function (): void {
        Route::get('/', [App\Http\Controllers\Modules\Inspection\InspectionController::class, 'index'])->name('index')->middleware('permission:inspection.checklists.view');
        Route::get('/create', [App\Http\Controllers\Modules\Inspection\InspectionController::class, 'create'])->name('create')->middleware('permission:inspection.checklists.create');
        Route::post('/', [App\Http\Controllers\Modules\Inspection\InspectionController::class, 'store'])->name('store')->middleware('permission:inspection.checklists.create');
        Route::get('/export', [App\Http\Controllers\Modules\Inspection\InspectionController::class, 'export'])->name('export')->middleware('permission:inspection.checklists.export');
        Route::get('/{inspection}', [App\Http\Controllers\Modules\Inspection\InspectionController::class, 'show'])->name('show')->middleware('permission:inspection.checklists.view');
        Route::put('/{inspection}', [App\Http\Controllers\Modules\Inspection\InspectionController::class, 'update'])->name('update')->middleware('permission:inspection.checklists.execute');
        Route::post('/{inspection}/start', [App\Http\Controllers\Modules\Inspection\InspectionController::class, 'start'])->name('start')->middleware('permission:inspection.checklists.execute');
        Route::post('/{inspection}/complete', [App\Http\Controllers\Modules\Inspection\InspectionController::class, 'complete'])->name('complete')->middleware('permission:inspection.checklists.execute');
    });

// Document Control Module
Route::middleware(['auth', 'verified'])
    ->prefix('documents')
    ->name('document.control.')
    ->group(function (): void {
        Route::get('/', [DocumentControlController::class, 'index'])->name('index')->middleware('permission:document.control.view');
        Route::get('/create', [DocumentControlController::class, 'create'])->name('create')->middleware('permission:document.control.create');
        Route::post('/', [DocumentControlController::class, 'store'])->name('store')->middleware('permission:document.control.create');
        Route::get('/export', [DocumentControlController::class, 'export'])->name('export')->middleware('permission:document.control.export');
        Route::get('/{controlledDocument}', [DocumentControlController::class, 'show'])->name('show')->middleware('permission:document.control.view');
        Route::get('/{controlledDocument}/edit', [DocumentControlController::class, 'edit'])->name('edit')->middleware('permission:document.control.update');
        Route::put('/{controlledDocument}', [DocumentControlController::class, 'update'])->name('update')->middleware('permission:document.control.update');
        Route::post('/{controlledDocument}/submit-review', [DocumentControlController::class, 'submitReview'])->name('submitReview')->middleware('permission:document.control.submit_review');
        Route::post('/{controlledDocument}/approve', [DocumentControlController::class, 'approve'])->name('approve')->middleware('permission:document.control.approve');
        Route::post('/{controlledDocument}/make-effective', [DocumentControlController::class, 'makeEffective'])->name('makeEffective')->middleware('permission:document.control.make_effective');
        Route::post('/{controlledDocument}/obsolete', [DocumentControlController::class, 'obsolete'])->name('obsolete')->middleware('permission:document.control.obsolete');
        Route::post('/{controlledDocument}/reject', [DocumentControlController::class, 'reject'])->name('reject')->middleware('permission:document.control.approve');
        Route::post('/{controlledDocument}/revise', [DocumentControlController::class, 'revise'])->name('revise')->middleware('permission:document.control.update');
        Route::post('/{controlledDocument}/comments', [DocumentControlController::class, 'comment'])->name('comments.store')->middleware('permission:document.control.view');
        Route::get('/{controlledDocument}/files/{file}/download', [DocumentControlController::class, 'download'])->name('files.download')->middleware('permission:document.control.view');
    });

// Audit Management Module
Route::middleware(['auth', 'verified'])
    ->prefix('audits')
    ->name('audits.')
    ->group(function (): void {
        Route::get('/', [AuditController::class, 'index'])->name('index')->middleware('permission:audit.management.view');
        Route::get('/create', [AuditController::class, 'create'])->name('create')->middleware('permission:audit.management.create');
        Route::post('/', [AuditController::class, 'store'])->name('store')->middleware('permission:audit.management.create');
        Route::get('/export', [AuditController::class, 'export'])->name('export')->middleware('permission:audit.management.export');
        Route::get('/{audit}', [AuditController::class, 'show'])->name('show')->middleware('permission:audit.management.view');
        Route::get('/{audit}/edit', [AuditController::class, 'edit'])->name('edit')->middleware('permission:audit.management.update');
        Route::put('/{audit}', [AuditController::class, 'update'])->name('update')->middleware('permission:audit.management.update');
        Route::post('/{audit}/start', [AuditController::class, 'startAudit'])->name('start')->middleware('permission:audit.management.execute');
        Route::post('/{audit}/generate-report', [AuditController::class, 'generateReport'])->name('generate-report')->middleware('permission:audit.management.execute');
        Route::post('/{audit}/close', [AuditController::class, 'closeAudit'])->name('close')->middleware('permission:audit.management.close');
        Route::post('/{audit}/findings', [AuditController::class, 'storeFinding'])->name('findings.store')->middleware('permission:audit.findings.create');
        Route::put('/{audit}/findings/{finding}', [AuditController::class, 'updateFinding'])->name('findings.update')->middleware('permission:audit.findings.update');
        Route::post('/{audit}/findings/{finding}/close', [AuditController::class, 'closeFinding'])->name('findings.close')->middleware('permission:audit.findings.close');
        Route::post('/{audit}/comment', [AuditController::class, 'comment'])->name('comment')->middleware('permission:core.comments.create');
        Route::post('/{audit}/files', [AuditController::class, 'uploadEvidence'])->name('files.store')->middleware('permission:core.files.upload');
        Route::get('/{audit}/files/{file}/download', [AuditController::class, 'downloadEvidence'])->name('files.download')->middleware('permission:core.files.download');
    });

// Phase 8: Training & Competency Management
require __DIR__.'/modules/training.php';

// Phase 9: Permit to Work
require __DIR__.'/modules/permit.php';

// Phase 10: Environmental Management
require __DIR__.'/modules/environment.php';

// Phase 11: Security Management
require __DIR__.'/modules/security.php';

// Phase 12: Quality Management
require __DIR__.'/modules/quality.php';

// Phase 13: Risk Management
require __DIR__.'/modules/risk.php';
require __DIR__ . '/modules/legal.php';

// Phase 15: Emergency Preparedness
require __DIR__ . '/modules/emergency.php';

// Phase 16: Contractor Management
require __DIR__ . '/modules/contractor.php';

// Phase 17: Asset & Equipment Safety
require __DIR__ . '/modules/asset.php';

// Phase 18: Communication & Campaign
require __DIR__ . '/modules/communication.php';

// Phase 19: Advanced Reporting & Export
require __DIR__ . '/modules/reporting.php';
