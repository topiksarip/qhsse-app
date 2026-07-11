<?php

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
