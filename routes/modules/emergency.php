<?php

declare(strict_types=1);

use App\Http\Controllers\Modules\EmergencyPreparedness\EmergencyContactController;
use App\Http\Controllers\Modules\EmergencyPreparedness\EmergencyDrillController;
use App\Http\Controllers\Modules\EmergencyPreparedness\EmergencyPlanController;
use Illuminate\Support\Facades\Route;

// Emergency Plans
Route::resource('plans', EmergencyPlanController::class)
    ->names('emergency.plans')
    ->parameters(['plans' => 'plan']);

Route::get('plans/export', [EmergencyPlanController::class, 'export'])
    ->name('emergency.plans.export');

// Emergency Drills
Route::resource('drills', EmergencyDrillController::class)
    ->names('emergency.drills')
    ->parameters(['drills' => 'drill']);

Route::post('drills/{drill}/execute', [EmergencyDrillController::class, 'execute'])
    ->name('emergency.drills.execute');

Route::get('drills/export', [EmergencyDrillController::class, 'export'])
    ->name('emergency.drills.export');

// Emergency Contacts
Route::resource('contacts', EmergencyContactController::class)
    ->names('emergency.contacts')
    ->parameters(['contacts' => 'contact']);
