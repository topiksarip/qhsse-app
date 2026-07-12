<?php
use App\Http\Controllers\Modules\Quality\CustomerComplaintController;
use App\Http\Controllers\Modules\Quality\NcrController;
use Illuminate\Support\Facades\Route;
Route::middleware(['auth','verified'])->prefix('quality')->name('quality.')->group(function(){
    Route::prefix('ncrs')->name('ncrs.')->group(function(){
        Route::get('/',[NcrController::class,'index'])->name('index')->middleware('permission:quality.ncrs.view');
        Route::get('/create',[NcrController::class,'create'])->name('create')->middleware('permission:quality.ncrs.create');
        Route::post('/',[NcrController::class,'store'])->name('store')->middleware('permission:quality.ncrs.create');
        Route::get('/export',[NcrController::class,'export'])->name('export')->middleware('permission:quality.ncrs.export');
        Route::get('/{ncr}',[NcrController::class,'show'])->name('show')->middleware('permission:quality.ncrs.view');
        Route::get('/{ncr}/edit',[NcrController::class,'edit'])->name('edit')->middleware('permission:quality.ncrs.update');
        Route::put('/{ncr}',[NcrController::class,'update'])->name('update')->middleware('permission:quality.ncrs.update');
    });
    
    Route::prefix('complaints')->name('complaints.')->group(function(){
        Route::get('/',[CustomerComplaintController::class,'index'])->name('index')->middleware('permission:quality.complaints.view');
        Route::get('/create',[CustomerComplaintController::class,'create'])->name('create')->middleware('permission:quality.complaints.view');
        Route::post('/',[CustomerComplaintController::class,'store'])->name('store')->middleware('permission:quality.complaints.view');
        Route::get('/export',[CustomerComplaintController::class,'export'])->name('export')->middleware('permission:quality.complaints.view');
        Route::get('/{complaint}',[CustomerComplaintController::class,'show'])->name('show')->middleware('permission:quality.complaints.view');
        Route::get('/{complaint}/edit',[CustomerComplaintController::class,'edit'])->name('edit')->middleware('permission:quality.complaints.view');
        Route::put('/{complaint}',[CustomerComplaintController::class,'update'])->name('update')->middleware('permission:quality.complaints.view');
        Route::post('/{complaint}/close',[CustomerComplaintController::class,'close'])->name('close')->middleware('permission:quality.complaints.view');
    });
});
