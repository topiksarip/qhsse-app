<?php

use App\Http\Controllers\Modules\Training\TrainingMatrixController;
use App\Http\Controllers\Modules\Training\TrainingProgramController;
use App\Http\Controllers\Modules\Training\TrainingRecordController;
use Illuminate\Support\Facades\Route;

// Training Programs
Route::middleware(['auth', 'verified'])
    ->prefix('training-programs')
    ->name('training.programs.')
    ->group(function (): void {
        Route::get('/', [TrainingProgramController::class, 'index'])
            ->name('index')
            ->middleware('permission:training.programs.view');

        Route::get('/create', [TrainingProgramController::class, 'create'])
            ->name('create')
            ->middleware('permission:training.programs.create');

        Route::post('/', [TrainingProgramController::class, 'store'])
            ->name('store')
            ->middleware('permission:training.programs.create');

        Route::get('/{program}', [TrainingProgramController::class, 'show'])
            ->name('show')
            ->middleware('permission:training.programs.view');

        Route::get('/{program}/edit', [TrainingProgramController::class, 'edit'])
            ->name('edit')
            ->middleware('permission:training.programs.update');

        Route::put('/{program}', [TrainingProgramController::class, 'update'])
            ->name('update')
            ->middleware('permission:training.programs.update');
    });

// Training Records
Route::middleware(['auth', 'verified'])
    ->prefix('training-records')
    ->name('training.records.')
    ->group(function (): void {
        Route::get('/', [TrainingRecordController::class, 'index'])
            ->name('index')
            ->middleware('permission:training.records.view');

        Route::get('/export', [TrainingRecordController::class, 'export'])
            ->name('export')
            ->middleware('permission:training.records.export');

        Route::get('/create', [TrainingRecordController::class, 'create'])
            ->name('create')
            ->middleware('permission:training.records.create');

        Route::post('/', [TrainingRecordController::class, 'store'])
            ->name('store')
            ->middleware('permission:training.records.create');

        Route::get('/{record}', [TrainingRecordController::class, 'show'])
            ->name('show')
            ->middleware('permission:training.records.view');

        Route::get('/{record}/edit', [TrainingRecordController::class, 'edit'])
            ->name('edit')
            ->middleware('permission:training.records.update');

        Route::put('/{record}', [TrainingRecordController::class, 'update'])
            ->name('update')
            ->middleware('permission:training.records.update');
        Route::delete('/{record}', [\App\Http\Controllers\Modules\Training\TrainingRecordController::class, 'destroy'])->name('destroy')->middleware('permission:training.records.delete');
    });

// Training Matrix
Route::middleware(['auth', 'verified'])
    ->prefix('training')
    ->name('training.')
    ->group(function (): void {
        Route::get('/matrix', [TrainingMatrixController::class, 'index'])
            ->name('matrix.index')
            ->middleware('permission:training.records.view');
    });
