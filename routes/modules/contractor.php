<?php

use App\Http\Controllers\Modules\Contractor\ContractorController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified'])->prefix('contractors')->group(function () {
    // Export before resource routes
    Route::get('export', [ContractorController::class, 'export'])
        ->name('contractors.export');

    // Standard resource routes
    Route::resource('contractors', ContractorController::class)
        ->parameters(['contractors' => 'contractor'])
        ->names([
            'index' => 'contractors.index',
            'create' => 'contractors.create',
            'store' => 'contractors.store',
            'show' => 'contractors.show',
            'edit' => 'contractors.edit',
            'update' => 'contractors.update',
            'destroy' => 'contractors.destroy',
        ]);
});
