<?php

namespace App\Http\Controllers\Core;

use App\Http\Controllers\Controller;
use App\Models\Core\Audit\AuditLog;
use App\Models\Core\MasterData\Company;
use App\Models\Core\MasterData\Site;
use App\Models\Core\Users\Employee;
use App\Models\User;
use Inertia\Inertia;
use Inertia\Response;

class AdminDashboardController extends Controller
{
    public function __invoke(): Response
    {
        return Inertia::render('Core/Admin/Dashboard', [
            'stats' => [
                'users' => User::count(),
                'activeUsers' => User::where('is_active', true)->count(),
                'employees' => Employee::count(),
                'sites' => Site::count(),
                'companies' => Company::count(),
            ],
            'recentActivity' => AuditLog::query()
                ->with('actor:id,name')
                ->latest()->limit(10)
                ->get(['id', 'event', 'module_name', 'actor_id', 'actor_name', 'created_at']),
        ]);
    }
}
