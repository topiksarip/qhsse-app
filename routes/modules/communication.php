<?php

use App\Http\Controllers\Modules\Communication\CampaignController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified'])->group(function () {
    // Campaign CRUD
    Route::resource('campaigns', CampaignController::class);
    
    // Campaign publish action
    Route::post('campaigns/{campaign}/publish', [CampaignController::class, 'publish'])
        ->name('campaigns.publish');
    
    // Campaign acknowledge action
    Route::post('campaigns/{campaign}/acknowledge', [CampaignController::class, 'acknowledge'])
        ->name('campaigns.acknowledge');
    
    // Campaign export
    Route::get('campaigns-export', [CampaignController::class, 'export'])
        ->name('campaigns.export');
});
