<?php

use App\Http\Controllers\Modules\Permit\PermitController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified'])->prefix('permit')->name('permit.')->group(function () {
    // Permit to Work Management
    Route::prefix('work')->name('work.')->group(function () {
        Route::get('/', [PermitController::class, 'index'])->name('index')->middleware('permission:permit.work.view');
        Route::get('/create', [PermitController::class, 'create'])->name('create')->middleware('permission:permit.work.create');
        Route::post('/', [PermitController::class, 'store'])->name('store')->middleware('permission:permit.work.create');
        Route::get('/export', [PermitController::class, 'export'])->name('export')->middleware('permission:permit.work.export');
        Route::get('/{permit}', [PermitController::class, 'show'])->name('show')->middleware('permission:permit.work.view');
        Route::get('/{permit}/edit', [PermitController::class, 'edit'])->name('edit')->middleware('permission:permit.work.update');
        Route::put('/{permit}', [PermitController::class, 'update'])->name('update')->middleware('permission:permit.work.update');
        Route::post('/{permit}/checklist/sign', [PermitController::class, 'signChecklist'])->name('checklist.sign')->middleware('permission:permit.work.checklist');
        Route::post('/{permit}/transition', [PermitController::class, 'transition'])->name('transition');
    });
});
