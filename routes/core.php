<?php

use App\Http\Controllers\Core\AreaController;
use App\Http\Controllers\Core\AuditLogController;
use App\Http\Controllers\Core\CategoryController;
use App\Http\Controllers\Core\CommentActivityController;
use App\Http\Controllers\Core\CompanyController;
use App\Http\Controllers\Core\DepartmentController;
use App\Http\Controllers\Core\EmployeeController;
use App\Http\Controllers\Core\ManagedFileController;
use App\Http\Controllers\Core\NotificationController;
use App\Http\Controllers\Core\NumberingFormatController;
use App\Http\Controllers\Core\PositionController;
use App\Http\Controllers\Core\PriorityController;
use App\Http\Controllers\Core\RiskMatrixLevelController;
use App\Http\Controllers\Core\RolePermissionController;
use App\Http\Controllers\Core\SeverityController;
use App\Http\Controllers\Core\SiteController;
use App\Http\Controllers\Core\StatusController;
use App\Http\Controllers\Core\UserAdminController;
use App\Http\Controllers\Core\WorkflowController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Core Routes
|--------------------------------------------------------------------------
|
| Shared platform routes for Phase 0 Core Foundation live here. Keep
| business module routes in routes/modules.php or routes/modules/*.php.
|
*/

Route::middleware(['auth', 'verified', 'active'])->prefix('core')->name('core.')->group(function (): void {
    Route::get('/health', fn () => response()->json([
        'status' => 'ok',
        'scope' => 'core-foundation',
    ]))->name('health');

    foreach ([
        'severities' => [SeverityController::class, 'severity'],
        'priorities' => [PriorityController::class, 'priority'],
        'statuses' => [StatusController::class, 'status'],
        'categories' => [CategoryController::class, 'category'],
        'risk-matrix' => [RiskMatrixLevelController::class, 'riskMatrixLevel'],
    ] as $resource => [$controller, $param]) {
        $parameter = '{'.$param.'}';
        Route::middleware("permission:core.{$resource}.view")->get($resource, [$controller, 'index'])->name("{$resource}.index");
        Route::middleware("permission:core.{$resource}.create")->get("{$resource}/create", [$controller, 'create'])->name("{$resource}.create");
        Route::middleware("permission:core.{$resource}.create")->post($resource, [$controller, 'store'])->name("{$resource}.store");
        Route::middleware("permission:core.{$resource}.update")->get("{$resource}/{$parameter}/edit", [$controller, 'edit'])->name("{$resource}.edit");
        Route::middleware("permission:core.{$resource}.update")
            ->match(['put', 'patch'], "{$resource}/{$parameter}", [$controller, 'update'])
            ->name("{$resource}.update");
        Route::middleware("permission:core.{$resource}.deactivate")->delete("{$resource}/{$parameter}", [$controller, 'destroy'])->name("{$resource}.destroy");
    }

    foreach ([
        'sites' => [SiteController::class, 'site'],
        'areas' => [AreaController::class, 'area'],
        'departments' => [DepartmentController::class, 'department'],
        'positions' => [PositionController::class, 'position'],
    ] as $resource => [$controller, $param]) {
        $parameter = '{'.$param.'}';
        Route::middleware("permission:core.{$resource}.view")->get($resource, [$controller, 'index'])->name("{$resource}.index");
        if (in_array($resource, ['sites', 'departments'], true)) {
            Route::middleware('permission:core.export.csv')->get("{$resource}/export", [$controller, 'export'])->name("{$resource}.export");
        }
        Route::middleware("permission:core.{$resource}.create")->get("{$resource}/create", [$controller, 'create'])->name("{$resource}.create");
        Route::middleware("permission:core.{$resource}.create")->post($resource, [$controller, 'store'])->name("{$resource}.store");
        Route::middleware("permission:core.{$resource}.update")->get("{$resource}/{$parameter}/edit", [$controller, 'edit'])->name("{$resource}.edit");
        Route::middleware("permission:core.{$resource}.update")
            ->match(['put', 'patch'], "{$resource}/{$parameter}", [$controller, 'update'])
            ->name("{$resource}.update");
        Route::middleware("permission:core.{$resource}.deactivate")->delete("{$resource}/{$parameter}", [$controller, 'destroy'])->name("{$resource}.destroy");
    }

    Route::middleware('permission:core.files.view')->get('files', [ManagedFileController::class, 'index'])->name('files.index');
    Route::middleware('permission:core.files.upload')->get('files/create', [ManagedFileController::class, 'create'])->name('files.create');
    Route::middleware('permission:core.files.upload')->post('files', [ManagedFileController::class, 'store'])->name('files.store');
    Route::middleware('permission:core.files.download')->get('files/{file}/download', [ManagedFileController::class, 'download'])->name('files.download');
    Route::middleware('permission:core.files.delete')->delete('files/{file}', [ManagedFileController::class, 'destroy'])->name('files.destroy');

    Route::middleware('permission:core.numbering.view')->get('numbering', [NumberingFormatController::class, 'index'])->name('numbering.index');
    Route::middleware('permission:core.numbering.create')->get('numbering/create', [NumberingFormatController::class, 'create'])->name('numbering.create');
    Route::middleware('permission:core.numbering.create')->post('numbering', [NumberingFormatController::class, 'store'])->name('numbering.store');
    Route::middleware('permission:core.numbering.update')->get('numbering/{numbering_format}/edit', [NumberingFormatController::class, 'edit'])->name('numbering.edit');
    Route::middleware('permission:core.numbering.update')->put('numbering/{numbering_format}', [NumberingFormatController::class, 'update'])->name('numbering.update');
    Route::middleware('permission:core.numbering.generate')->post('numbering/generate', [NumberingFormatController::class, 'generate'])->name('numbering.generate');

    Route::middleware('permission:core.workflow.view')->get('workflow', [WorkflowController::class, 'index'])->name('workflow.index');
    Route::middleware('permission:core.workflow.view')->get('workflow/history', [WorkflowController::class, 'history'])->name('workflow.history');
    Route::middleware('permission:core.workflow.view')->get('workflow/{definition}', [WorkflowController::class, 'show'])->name('workflow.show');
    Route::middleware('permission:core.workflow.transition')->post('workflow/run', [WorkflowController::class, 'run'])->name('workflow.run');

    Route::middleware('permission:core.audit.view')->get('audit-logs', [AuditLogController::class, 'index'])->name('audit-logs.index');
    Route::middleware('permission:core.audit.view')->get('audit-logs/{auditLog}', [AuditLogController::class, 'show'])->name('audit-logs.show');

    Route::middleware('permission:core.comments.view')->get('comments-activity', [CommentActivityController::class, 'index'])->name('comments-activity.index');
    Route::middleware('permission:core.comments.create')->post('comments', [CommentActivityController::class, 'store'])->name('comments.store');
    Route::middleware('permission:core.comments.delete')->delete('comments/{comment}', [CommentActivityController::class, 'destroy'])->name('comments.destroy');

    Route::middleware('permission:core.notifications.view')->get('notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::middleware('permission:core.notifications.manage')->post('notifications/test', [NotificationController::class, 'test'])->name('notifications.test');
    Route::middleware('permission:core.notifications.view')->patch('notifications/{notification}/read', [NotificationController::class, 'markRead'])->name('notifications.read');
    Route::middleware('permission:core.notifications.view')->patch('notifications/{notification}/unread', [NotificationController::class, 'markUnread'])->name('notifications.unread');
    Route::middleware('permission:core.notifications.view')->patch('notifications/read-all', [NotificationController::class, 'markAllRead'])->name('notifications.read-all');

    Route::middleware('permission:core.companies.view')->group(function (): void {
        Route::get('companies', [CompanyController::class, 'index'])->name('companies.index');
    });
    Route::middleware('permission:core.companies.create')->group(function (): void {
        Route::get('companies/create', [CompanyController::class, 'create'])->name('companies.create');
        Route::post('companies', [CompanyController::class, 'store'])->name('companies.store');
    });
    Route::middleware('permission:core.companies.update')->group(function (): void {
        Route::get('companies/{company}/edit', [CompanyController::class, 'edit'])->name('companies.edit');
        Route::match(['put', 'patch'], 'companies/{company}', [CompanyController::class, 'update'])->name('companies.update');
    });
    Route::delete('companies/{company}', [CompanyController::class, 'destroy'])
        ->middleware('permission:core.companies.deactivate')
        ->name('companies.destroy');

    Route::middleware('permission:core.employees.view')->group(function (): void {
        Route::get('employees', [EmployeeController::class, 'index'])->name('employees.index');
    });
    Route::middleware('permission:core.employees.create')->group(function (): void {
        Route::get('employees/create', [EmployeeController::class, 'create'])->name('employees.create');
        Route::post('employees', [EmployeeController::class, 'store'])->name('employees.store');
    });
    Route::middleware('permission:core.employees.update')->group(function (): void {
        Route::get('employees/{employee}/edit', [EmployeeController::class, 'edit'])->name('employees.edit');
        Route::match(['put', 'patch'], 'employees/{employee}', [EmployeeController::class, 'update'])->name('employees.update');
    });
    Route::delete('employees/{employee}', [EmployeeController::class, 'destroy'])
        ->middleware('permission:core.employees.deactivate')
        ->name('employees.destroy');

    Route::middleware('permission:core.users.view')->group(function (): void {
        Route::get('users', [UserAdminController::class, 'index'])->name('users.index');
    });
    Route::middleware('permission:core.users.create')->group(function (): void {
        Route::get('users/create', [UserAdminController::class, 'create'])->name('users.create');
        Route::post('users', [UserAdminController::class, 'store'])->name('users.store');
    });
    Route::middleware('permission:core.users.update')->group(function (): void {
        Route::get('users/{user}/edit', [UserAdminController::class, 'edit'])->name('users.edit');
        Route::match(['put', 'patch'], 'users/{user}', [UserAdminController::class, 'update'])->name('users.update');
    });
    Route::delete('users/{user}', [UserAdminController::class, 'destroy'])
        ->middleware('permission:core.users.deactivate')
        ->name('users.destroy');

    Route::middleware('permission:core.roles.manage')->group(function (): void {
        Route::get('roles', [RolePermissionController::class, 'index'])->name('roles.index');
        Route::put('roles/{role}/permissions', [RolePermissionController::class, 'update'])->name('roles.permissions.update');
    });
});
