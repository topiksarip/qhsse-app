<?php

declare(strict_types=1);

use App\Http\Controllers\Modules\RiskManagement\RiskRegisterController;
use Illuminate\Support\Facades\Route;

// Phase 13: Risk Management (HIRADC/JSA)
Route::middleware(['auth', 'verified'])
    ->prefix('risk-registers')
    ->name('risk.registers.')
    ->group(function (): void {
        Route::get('/', [RiskRegisterController::class, 'index'])
            ->name('index')
            ->middleware('permission:risk.registers.view');

        Route::get('/create', [RiskRegisterController::class, 'create'])
            ->name('create')
            ->middleware('permission:risk.registers.create');

        Route::post('/', [RiskRegisterController::class, 'store'])
            ->name('store')
            ->middleware('permission:risk.registers.create');

        Route::get('/export', [RiskRegisterController::class, 'export'])
            ->name('export')
            ->middleware('permission:risk.registers.export');

        Route::get('/{riskRegister}', [RiskRegisterController::class, 'show'])
            ->name('show')
            ->middleware('permission:risk.registers.view');

        Route::get('/{riskRegister}/edit', [RiskRegisterController::class, 'edit'])
            ->name('edit')
            ->middleware('permission:risk.registers.update');

        Route::put('/{riskRegister}', [RiskRegisterController::class, 'update'])
            ->name('update')
            ->middleware('permission:risk.registers.update');
        Route::delete('/{riskRegister}', [\App\Http\Controllers\Modules\RiskManagement\RiskRegisterController::class, 'destroy'])->name('destroy')->middleware('permission:risk.registers.delete');

        Route::post('/{riskRegister}/assess', [RiskRegisterController::class, 'assess'])
            ->name('assess')
            ->middleware('permission:risk.registers.assess');

        Route::post('/{riskRegister}/needs-controls', [RiskRegisterController::class, 'needsControls'])
            ->name('needs_controls')
            ->middleware('permission:risk.registers.assess');

        Route::post('/{riskRegister}/implement-controls', [RiskRegisterController::class, 'implementControls'])
            ->name('implement_controls')
            ->middleware('permission:risk.registers.assess');

        Route::post('/{riskRegister}/monitor', [RiskRegisterController::class, 'monitor'])
            ->name('monitor')
            ->middleware('permission:risk.registers.assess');

        Route::post('/{riskRegister}/obsolete', [RiskRegisterController::class, 'obsolete'])
            ->name('obsolete')
            ->middleware('permission:risk.registers.assess');
    });
