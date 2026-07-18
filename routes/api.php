<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes (JSON, Flutter / mobile clients)
|--------------------------------------------------------------------------
|
| These routes are stateless and authenticated via Laravel Sanctum
| bearer tokens. They mirror the Inertia web routes but return JSON
| envelopes (see App\Http\Resources\ApiResponse). Reuse core services
| (NumberingService, WorkflowService, AuditService, etc.) — do NOT
| duplicate business logic from the web controllers.
|
*/

Route::prefix('v1')->group(function (): void {
    require __DIR__.'/api/v1.php';
});
