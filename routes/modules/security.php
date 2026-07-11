<?php

use App\Http\Controllers\Modules\Security\SecurityIncidentController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified'])->prefix('security')->name('security.')->group(function () {
    Route::prefix('incidents')->name('incidents.')->group(function () {
        Route::get('/', [SecurityIncidentController::class, 'index'])->name('index')->middleware('permission:security.incidents.view');
        Route::get('/create', [SecurityIncidentController::class, 'create'])->name('create')->middleware('permission:security.incidents.create');
        Route::post('/', [SecurityIncidentController::class, 'store'])->name('store')->middleware('permission:security.incidents.create');
        Route::get('/export', [SecurityIncidentController::class, 'export'])->name('export')->middleware('permission:security.incidents.export');
        Route::get('/{security_incident}', [SecurityIncidentController::class, 'show'])->name('show')->middleware('permission:security.incidents.view');
        Route::get('/{security_incident}/edit', [SecurityIncidentController::class, 'edit'])->name('edit')->middleware('permission:security.incidents.update');
        Route::put('/{security_incident}', [SecurityIncidentController::class, 'update'])->name('update')->middleware('permission:security.incidents.update');
    });
});
