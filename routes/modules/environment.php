<?php

use App\Http\Controllers\Modules\Environment\EnvironmentalRecordController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified'])->prefix('environment')->name('environment.')->group(function () {
    // Environmental Records Management
    Route::prefix('records')->name('records.')->group(function () {
        Route::get('/', [EnvironmentalRecordController::class, 'index'])->name('index')->middleware('permission:environment.records.view');
        Route::get('/create', [EnvironmentalRecordController::class, 'create'])->name('create')->middleware('permission:environment.records.create');
        Route::post('/', [EnvironmentalRecordController::class, 'store'])->name('store')->middleware('permission:environment.records.create');
        Route::get('/export', [EnvironmentalRecordController::class, 'export'])->name('export')->middleware('permission:environment.records.export');
        Route::get('/{environmental_record}', [EnvironmentalRecordController::class, 'show'])->name('show')->middleware('permission:environment.records.view');
        Route::get('/{environmental_record}/edit', [EnvironmentalRecordController::class, 'edit'])->name('edit')->middleware('permission:environment.records.update');
        Route::put('/{environmental_record}', [EnvironmentalRecordController::class, 'update'])->name('update')->middleware('permission:environment.records.update');
        Route::post('/{environmental_record}/investigate', [EnvironmentalRecordController::class, 'investigate'])->name('investigate')->middleware('permission:environment.records.approve');
        Route::post('/{environmental_record}/open-action', [EnvironmentalRecordController::class, 'openAction'])->name('open-action')->middleware('permission:environment.records.approve');
        Route::post('/{environmental_record}/close', [EnvironmentalRecordController::class, 'close'])->name('close')->middleware('permission:environment.records.close');
    });
});
