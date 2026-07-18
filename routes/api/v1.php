<?php

use App\Http\Controllers\Api\V1\Auth\AuthController;
use App\Http\Controllers\Api\V1\IncidentReportController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API v1 Routes
|--------------------------------------------------------------------------
|
| Authenticated via Sanctum bearer token. Each resource mirrors the
| Inertia web resource but returns JSON. Business logic is delegated to
| the same core services used by the web controllers.
|
| NOTE: Use the `api.permission` alias (not Spatie's `permission`) for
| token-authenticated routes. It resolves the user from the `sanctum`
| guard but checks permissions under the default `web` guard where the
| permissions are seeded — avoiding Spatie's guard_name mismatch.
*/

Route::post('/auth/login', [AuthController::class, 'login']);
Route::post('/auth/logout', [AuthController::class, 'logout'])->middleware('auth:sanctum');
Route::get('/auth/me', [AuthController::class, 'me'])->middleware('auth:sanctum');

Route::middleware('auth:sanctum')->group(function (): void {
    Route::get('/core/sites', fn () => \App\Http\Resources\ApiResponse::collection(
        \App\Models\Core\MasterData\Site::where('is_active', true)->orderBy('name')->get(['id', 'name'])
    ));

    // Incidents (explicit routes so each verb gets the right permission)
    Route::get('/incidents', [IncidentReportController::class, 'index'])
        ->middleware('api.permission:incident.reports.view')->name('incidents.index');
    Route::post('/incidents', [IncidentReportController::class, 'store'])
        ->middleware('api.permission:incident.reports.create')->name('incidents.store');
    Route::get('/incidents/{incidentReport}', [IncidentReportController::class, 'show'])
        ->middleware('api.permission:incident.reports.view')->name('incidents.show');
    Route::put('/incidents/{incidentReport}', [IncidentReportController::class, 'update'])
        ->middleware('api.permission:incident.reports.update')->name('incidents.update');
    Route::delete('/incidents/{incidentReport}', [IncidentReportController::class, 'destroy'])
        ->middleware('api.permission:incident.reports.delete')->name('incidents.destroy');

    // State transitions (incident workflow)
    Route::post('/incidents/{incidentReport}/submit', [IncidentReportController::class, 'submit'])
        ->middleware('api.permission:incident.reports.submit')->name('incidents.submit');
    Route::post('/incidents/{incidentReport}/review', [IncidentReportController::class, 'review'])
        ->middleware('api.permission:incident.reports.review')->name('incidents.review');
    Route::post('/incidents/{incidentReport}/close', [IncidentReportController::class, 'close'])
        ->middleware('api.permission:incident.reports.close')->name('incidents.close');
});
