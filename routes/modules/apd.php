<?php

use App\Http\Controllers\Modules\Apd\ApdCatalogController;
use App\Http\Controllers\Modules\Apd\ApdItemController;
use App\Http\Controllers\Modules\Apd\ApdIssuanceController;
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

        // Issuance sub-resource (workflow: request -> approve -> issue -> return/dispose)
        Route::prefix('issuances')->name('issuances.')->group(function (): void {
            Route::get('/export', [ApdIssuanceController::class, 'export'])
                ->name('export')
                ->middleware('permission:apd.export');

            Route::get('/', [ApdIssuanceController::class, 'index'])->name('index')->middleware('permission:apd.view');
            Route::get('/create', [ApdIssuanceController::class, 'create'])->name('create')->middleware('permission:apd.create');
            Route::post('/', [ApdIssuanceController::class, 'store'])->name('store')->middleware('permission:apd.request');
            Route::get('/{apd_issuance}', [ApdIssuanceController::class, 'show'])->name('show')->middleware('permission:apd.view');

            Route::post('/{apd_issuance}/request', [ApdIssuanceController::class, 'request'])->name('request')->middleware('permission:apd.request');
            Route::post('/{apd_issuance}/approve', [ApdIssuanceController::class, 'approve'])->name('approve')->middleware('permission:apd.approve');
            Route::post('/{apd_issuance}/issue', [ApdIssuanceController::class, 'issue'])->name('issue')->middleware('permission:apd.issue');
            Route::post('/{apd_issuance}/process', [ApdIssuanceController::class, 'process'])->name('process')->middleware('permission:apd.issue');
        });
    });
