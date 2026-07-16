<?php

use App\Http\Controllers\Modules\Apd\ApdCatalogController;
use App\Http\Controllers\Modules\Apd\ApdItemController;
use Illuminate\Support\Facades\Route;

// APD / PPE Management Routes
Route::middleware(['auth', 'verified', 'active'])
    ->scopeBindings()
    ->prefix('apd')
    ->name('apd.')
    ->group(function (): void {
        // Catalog sub-resource
        Route::prefix('catalogs')->name('catalogs.')->group(function (): void {
            Route::get('/export', [ApdCatalogController::class, 'export'])
                ->name('export')
                ->middleware('permission:apd.export');

            Route::get('/', [ApdCatalogController::class, 'index'])->name('index')->middleware('permission:apd.view');
            Route::get('/create', [ApdCatalogController::class, 'create'])->name('create')->middleware('permission:apd.create');
            Route::post('/', [ApdCatalogController::class, 'store'])->name('store')->middleware('permission:apd.create');
            Route::get('/{apd_catalog}', [ApdCatalogController::class, 'show'])->name('show')->middleware('permission:apd.view');
            Route::get('/{apd_catalog}/edit', [ApdCatalogController::class, 'edit'])->name('edit')->middleware('permission:apd.update');
            Route::put('/{apd_catalog}', [ApdCatalogController::class, 'update'])->name('update')->middleware('permission:apd.update');
            Route::delete('/{apd_catalog}', [ApdCatalogController::class, 'destroy'])->name('destroy')->middleware('permission:apd.delete');
        });

        // Inventory items sub-resource
        Route::prefix('items')->name('items.')->group(function (): void {
            Route::get('/export', [ApdItemController::class, 'export'])
                ->name('export')
                ->middleware('permission:apd.export');

            Route::get('/', [ApdItemController::class, 'index'])->name('index')->middleware('permission:apd.view');
            Route::get('/create', [ApdItemController::class, 'create'])->name('create')->middleware('permission:apd.create');
            Route::post('/', [ApdItemController::class, 'store'])->name('store')->middleware('permission:apd.create');
            Route::get('/{apd_item}', [ApdItemController::class, 'show'])->name('show')->middleware('permission:apd.view');
        });
    });
