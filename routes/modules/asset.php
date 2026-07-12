<?php

use App\Http\Controllers\Modules\Asset\AssetCertificateController;
use App\Http\Controllers\Modules\Asset\AssetController;
use App\Http\Controllers\Modules\Asset\AssetInspectionController;
use Illuminate\Support\Facades\Route;

// Asset Management Routes
Route::middleware(['auth', 'verified'])
    ->prefix('assets')
    ->name('assets.')
    ->group(function (): void {
        // Export must be before resource wildcards
        Route::get('/export', [AssetController::class, 'export'])
            ->name('export')
            ->middleware('permission:asset.management.export');

        // Asset CRUD
        Route::get('/', [AssetController::class, 'index'])->name('index')->middleware('permission:asset.management.view');
        Route::get('/create', [AssetController::class, 'create'])->name('create')->middleware('permission:asset.management.create');
        Route::post('/', [AssetController::class, 'store'])->name('store')->middleware('permission:asset.management.create');
        Route::get('/{asset}', [AssetController::class, 'show'])->name('show')->middleware('permission:asset.management.view');
        Route::get('/{asset}/edit', [AssetController::class, 'edit'])->name('edit')->middleware('permission:asset.management.update');
        Route::put('/{asset}', [AssetController::class, 'update'])->name('update')->middleware('permission:asset.management.update');
        Route::delete('/{asset}', [AssetController::class, 'destroy'])->name('destroy')->middleware('permission:asset.management.update');

        // Certificate Routes (nested under assets)
        Route::prefix('{asset}/certificates')->name('certificates.')->group(function (): void {
            Route::get('/', [AssetCertificateController::class, 'index'])->name('index')->middleware('permission:asset.certificates.view');
            Route::get('/create', [AssetCertificateController::class, 'create'])->name('create')->middleware('permission:asset.certificates.create');
            Route::post('/', [AssetCertificateController::class, 'store'])->name('store')->middleware('permission:asset.certificates.create');
            Route::get('/{certificate}', [AssetCertificateController::class, 'show'])->name('show')->middleware('permission:asset.certificates.view');
            Route::get('/{certificate}/edit', [AssetCertificateController::class, 'edit'])->name('edit')->middleware('permission:asset.certificates.update');
            Route::put('/{certificate}', [AssetCertificateController::class, 'update'])->name('update')->middleware('permission:asset.certificates.update');
            Route::delete('/{certificate}', [AssetCertificateController::class, 'destroy'])->name('destroy')->middleware('permission:asset.certificates.update');
            Route::get('/{certificate}/files/{file}/download', [AssetCertificateController::class, 'download'])->name('files.download')->middleware('permission:asset.certificates.view');
        });

        // Inspection Routes (nested under assets)
        Route::prefix('{asset}/inspections')->name('inspections.')->group(function (): void {
            Route::get('/', [AssetInspectionController::class, 'index'])->name('index')->middleware('permission:asset.inspections.view');
            Route::get('/create', [AssetInspectionController::class, 'create'])->name('create')->middleware('permission:asset.inspections.create');
            Route::post('/', [AssetInspectionController::class, 'store'])->name('store')->middleware('permission:asset.inspections.create');
            Route::get('/{inspection}', [AssetInspectionController::class, 'show'])->name('show')->middleware('permission:asset.inspections.view');
            Route::get('/{inspection}/edit', [AssetInspectionController::class, 'edit'])->name('edit')->middleware('permission:asset.inspections.create');
            Route::put('/{inspection}', [AssetInspectionController::class, 'update'])->name('update')->middleware('permission:asset.inspections.create');
            Route::delete('/{inspection}', [AssetInspectionController::class, 'destroy'])->name('destroy')->middleware('permission:asset.inspections.create');
            Route::post('/{inspection}/link-capa', [AssetInspectionController::class, 'linkCapa'])->name('link-capa')->middleware('permission:asset.inspections.create');
        });
    });
