<?php

use App\Http\Controllers\Modules\Audit\AuditController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'active'])->prefix('audits')->name('audits.')->group(function () {
    Route::get('/', [AuditController::class, 'index'])->name('index')
        ->can('audit.management.view');
    Route::get('/create', [AuditController::class, 'create'])->name('create')
        ->can('audit.management.create');
    Route::post('/', [AuditController::class, 'store'])->name('store')
        ->can('audit.management.create');
    Route::get('/export', [AuditController::class, 'export'])->name('export')
        ->can('audit.management.export');
    Route::get('/{audit}', [AuditController::class, 'show'])->name('show')
        ->can('view', 'audit');
    Route::get('/{audit}/edit', [AuditController::class, 'edit'])->name('edit')
        ->can('update', 'audit');
    Route::put('/{audit}', [AuditController::class, 'update'])->name('update')
        ->can('update', 'audit');

    // Workflow actions
    Route::post('/{audit}/start', [AuditController::class, 'startAudit'])->name('start')
        ->can('audit.management.execute');
    Route::post('/{audit}/generate-report', [AuditController::class, 'generateReport'])->name('generate-report')
        ->can('audit.management.execute');
    Route::post('/{audit}/close', [AuditController::class, 'closeAudit'])->name('close')
        ->can('audit.management.close');

    // Findings
    Route::post('/{audit}/findings', [AuditController::class, 'storeFinding'])->name('findings.store')
        ->can('audit.findings.create');
    Route::put('/{audit}/findings/{finding}', [AuditController::class, 'updateFinding'])->name('findings.update')
        ->can('audit.findings.update');
    Route::post('/{audit}/findings/{finding}/close', [AuditController::class, 'closeFinding'])->name('findings.close')
        ->can('audit.findings.close');

    // Comments
    Route::post('/{audit}/comment', [AuditController::class, 'comment'])->name('comment')
        ->can('core.comments.create');
});
