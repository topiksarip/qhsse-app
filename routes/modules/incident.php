<?php

use App\Http\Controllers\Modules\Incident\IncidentReportController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified'])->prefix('incident')->name('incident.')->group(function (): void {
    Route::middleware('permission:incident-reporting.view')->get('/', [IncidentReportController::class, 'index'])->name('index');
    Route::middleware('permission:incident-reporting.create')->get('/create', [IncidentReportController::class, 'create'])->name('create');
    Route::middleware('permission:incident-reporting.create')->post('/', [IncidentReportController::class, 'store'])->name('store');
    Route::middleware('permission:incident-reporting.view')->get('/{incident_report}', [IncidentReportController::class, 'show'])->name('show');
    Route::middleware('permission:incident-reporting.update')->get('/{incident_report}/edit', [IncidentReportController::class, 'edit'])->name('edit');
    Route::middleware('permission:incident-reporting.update')->put('/{incident_report}', [IncidentReportController::class, 'update'])->name('update');
    Route::middleware('permission:incident-reporting.update')->patch('/{incident_report}', [IncidentReportController::class, 'update'])->name('update');
    Route::middleware('permission:incident-reporting.delete')->delete('/{incident_report}', [IncidentReportController::class, 'destroy'])->name('destroy');
    Route::middleware('permission:incident-reporting.submit')->post('/{incident_report}/submit', [IncidentReportController::class, 'submit'])->name('submit');
    Route::middleware('permission:incident-reporting.review')->post('/{incident_report}/review', [IncidentReportController::class, 'review'])->name('review');
    Route::middleware('permission:incident-reporting.approve')->post('/{incident_report}/approve', [IncidentReportController::class, 'approve'])->name('approve');
    Route::middleware('permission:incident-reporting.reject')->post('/{incident_report}/reject', [IncidentReportController::class, 'reject'])->name('reject');
    Route::middleware('permission:incident-reporting.verify')->post('/{incident_report}/verify', [IncidentReportController::class, 'verify'])->name('verify');
    Route::middleware('permission:incident-reporting.close')->post('/{incident_report}/close', [IncidentReportController::class, 'close'])->name('close');
    Route::middleware('permission:incident-reporting.reopen')->post('/{incident_report}/reopen', [IncidentReportController::class, 'reopen'])->name('reopen');
});
