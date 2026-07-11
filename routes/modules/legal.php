<?php

declare(strict_types=1);

use App\Http\Controllers\Modules\LegalCompliance\LegalObligationController;
use App\Http\Controllers\Modules\LegalCompliance\LegalRegisterController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified'])->prefix('legal')->name('legal.')->group(function () {
    // Legal Register Routes
    Route::prefix('registers')->name('registers.')->group(function () {
        Route::get('/', [LegalRegisterController::class, 'index'])->name('index');
        Route::get('/create', [LegalRegisterController::class, 'create'])->name('create');
        Route::post('/', [LegalRegisterController::class, 'store'])->name('store');
        Route::get('/export', [LegalRegisterController::class, 'export'])->name('export');
        Route::get('/{register}', [LegalRegisterController::class, 'show'])->name('show');
        Route::get('/{register}/edit', [LegalRegisterController::class, 'edit'])->name('edit');
        Route::put('/{register}', [LegalRegisterController::class, 'update'])->name('update');
        Route::delete('/{register}', [LegalRegisterController::class, 'destroy'])->name('destroy');

        // Obligation Routes (nested under register)
        Route::prefix('{register}/obligations')->name('obligations.')->group(function () {
            Route::post('/', [LegalObligationController::class, 'store'])->name('store');
            Route::put('/{obligation}', [LegalObligationController::class, 'update'])->name('update');
            Route::post('/{obligation}/complete', [LegalObligationController::class, 'complete'])->name('complete');
            Route::delete('/{obligation}', [LegalObligationController::class, 'destroy'])->name('destroy');
        });
    });
});
