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
