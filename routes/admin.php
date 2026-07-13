<?php

use App\Http\Controllers\Core\AdminDashboardController;
use App\Http\Controllers\Core\BulkImportController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified', 'active'])->prefix('admin')->name('admin.')->group(function (): void {
    Route::get('/', AdminDashboardController::class)
        ->middleware('permission:core.sites.view')
        ->name('dashboard');

    Route::get('import', [BulkImportController::class, 'create'])
        ->middleware('permission:core.employees.create|core.sites.create|core.departments.create')
        ->name('import.create');
    Route::get('import/{type}/template', [BulkImportController::class, 'template'])
        ->whereIn('type', ['employees', 'sites', 'departments'])
        ->name('import.template');
    Route::post('import/{type}', [BulkImportController::class, 'store'])
        ->whereIn('type', ['employees', 'sites', 'departments'])
        ->name('import.store');
});
