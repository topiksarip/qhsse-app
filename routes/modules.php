<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Module Route Registry
|--------------------------------------------------------------------------
|
| Register QHSSE business module routes here. Phase 0 intentionally keeps
| these as placeholders; Incident and later modules are added in their own
| phases after Core Foundation is complete.
|
*/

Route::middleware(['auth', 'verified'])->prefix('modules')->name('modules.')->group(function (): void {
    require __DIR__.'/modules/incident.php';
    Route::get('/health', fn () => response()->json([
        'status' => 'ok',
        'scope' => 'module-registry',
    ]))->name('health');
});
