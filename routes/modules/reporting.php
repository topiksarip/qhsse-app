<?php

use App\Http\Controllers\Modules\Reporting\ReportTemplateController;
use App\Http\Controllers\Modules\Reporting\SavedReportController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified'])->group(function () {
    // Report Templates
    Route::resource('report-templates', ReportTemplateController::class);
    Route::post('report-templates/{report_template}/toggle-active', [ReportTemplateController::class, 'toggleActive'])
        ->name('report-templates.toggle-active');

    // Saved Reports
    Route::get('saved-reports/create', [SavedReportController::class, 'create'])
        ->name('saved-reports.create');
    Route::post('saved-reports', [SavedReportController::class, 'store'])
        ->name('saved-reports.store');
    Route::get('saved-reports', [SavedReportController::class, 'index'])
        ->name('saved-reports.index');
    Route::get('saved-reports/{saved_report}', [SavedReportController::class, 'show'])
        ->name('saved-reports.show');
    Route::get('saved-reports/{saved_report}/download', [SavedReportController::class, 'download'])
        ->name('saved-reports.download');
    Route::post('saved-reports/{saved_report}/regenerate', [SavedReportController::class, 'regenerate'])
        ->name('saved-reports.regenerate');
    Route::delete('saved-reports/{saved_report}', [SavedReportController::class, 'destroy'])
        ->name('saved-reports.destroy');
});
