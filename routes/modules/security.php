<?php

use App\Http\Controllers\Modules\Security\PatrolChecklistController;
use App\Http\Controllers\Modules\Security\PatrolResultController;
use App\Http\Controllers\Modules\Security\SecurityIncidentController;
use App\Http\Controllers\Modules\Security\VisitorLogController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified', 'active'])->prefix('security')->name('security.')->group(function () {
    Route::prefix('incidents')->name('incidents.')->group(function () {
        Route::get('/', [SecurityIncidentController::class, 'index'])->name('index')->middleware('permission:security.incidents.view');
        Route::get('/create', [SecurityIncidentController::class, 'create'])->name('create')->middleware('permission:security.incidents.create');
        Route::post('/', [SecurityIncidentController::class, 'store'])->name('store')->middleware('permission:security.incidents.create');
        Route::get('/export', [SecurityIncidentController::class, 'export'])->name('export')->middleware('permission:security.incidents.export');
        Route::get('/{security_incident}', [SecurityIncidentController::class, 'show'])->name('show')->middleware('permission:security.incidents.view');
        Route::get('/{security_incident}/edit', [SecurityIncidentController::class, 'edit'])->name('edit')->middleware('permission:security.incidents.update');
        Route::put('/{security_incident}', [SecurityIncidentController::class, 'update'])->name('update')->middleware('permission:security.incidents.update');
        Route::post('/{security_incident}/transition', [SecurityIncidentController::class, 'transition'])->name('transition')->middleware('permission:security.incidents.update');
    });

    Route::prefix('visitors')->name('visitors.')->group(function () {
        Route::get('/', [VisitorLogController::class, 'index'])->name('index')->middleware('permission:security.visitor.view');
        Route::get('/export', [VisitorLogController::class, 'export'])->name('export')->middleware('permission:security.visitor.view');
        Route::get('/create', [VisitorLogController::class, 'create'])->name('create')->middleware('permission:security.visitor.log');
        Route::post('/', [VisitorLogController::class, 'store'])->name('store')->middleware('permission:security.visitor.log');
        Route::get('/{visitor}', [VisitorLogController::class, 'show'])->name('show')->middleware('permission:security.visitor.view');
        Route::get('/{visitor}/edit', [VisitorLogController::class, 'edit'])->name('edit')->middleware('permission:security.visitor.log');
        Route::put('/{visitor}', [VisitorLogController::class, 'update'])->name('update')->middleware('permission:security.visitor.log');
        Route::post('/{visitor}/check-out', [VisitorLogController::class, 'checkOut'])->name('check-out')->middleware('permission:security.visitor.log');
    });

    Route::prefix('patrols')->name('patrols.')->group(function () {
        Route::get('/', [PatrolChecklistController::class, 'index'])->name('index')->middleware('permission:security.patrols.view');
        Route::get('/export', [PatrolChecklistController::class, 'export'])->name('export')->middleware('permission:security.patrols.export');
        Route::get('/create', [PatrolChecklistController::class, 'create'])->name('create')->middleware('permission:security.patrols.create');
        Route::post('/', [PatrolChecklistController::class, 'store'])->name('store')->middleware('permission:security.patrols.create');
        Route::get('/{patrol}', [PatrolChecklistController::class, 'show'])->name('show')->middleware('permission:security.patrols.view');
        Route::get('/{patrol}/edit', [PatrolChecklistController::class, 'edit'])->name('edit')->middleware('permission:security.patrols.create');
        Route::put('/{patrol}', [PatrolChecklistController::class, 'update'])->name('update')->middleware('permission:security.patrols.create');
        Route::post('/{patrol}/start', [PatrolChecklistController::class, 'start'])->name('start')->middleware('permission:security.patrols.execute');
        Route::post('/{patrol}/complete', [PatrolChecklistController::class, 'complete'])->name('complete')->middleware('permission:security.patrols.execute');
        Route::put('/{patrol}/results/{result}', [PatrolResultController::class, 'store'])->name('results.store')->middleware('permission:security.patrols.execute');
    });
});
