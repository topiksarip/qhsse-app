<?php

namespace App\Http\Controllers;

use App\Models\Core\MasterData\Department;
use App\Models\Core\MasterData\Site;
use App\Models\Core\Notifications\CoreNotification;
use App\Models\Core\Users\Employee;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $siteId = $request->integer('site_id') ?: null;
        $departmentId = $request->integer('department_id') ?: null;

        return Inertia::render('Dashboard', [
            'filters' => [
                'from' => $request->query('from', now()->startOfMonth()->toDateString()),
                'to' => $request->query('to', now()->toDateString()),
                'site_id' => $siteId,
                'department_id' => $departmentId,
            ],
            'filterOptions' => [
                'sites' => Site::query()->where('is_active', true)->orderBy('name')->get(['id', 'name']),
                'departments' => Department::query()->where('is_active', true)->orderBy('name')->get(['id', 'name', 'site_id']),
            ],
            'kpis' => [
                ['label' => 'Active Sites', 'value' => Site::query()->where('is_active', true)->count(), 'tone' => 'emerald'],
                ['label' => 'Departments', 'value' => Department::query()->when($siteId, fn ($query) => $query->where('site_id', $siteId))->where('is_active', true)->count(), 'tone' => 'sky'],
                ['label' => 'Employees', 'value' => Employee::query()->when($departmentId, fn ($query) => $query->where('department_id', $departmentId))->where('is_active', true)->count(), 'tone' => 'amber'],
                ['label' => 'Active Users', 'value' => User::query()->where('is_active', true)->count(), 'tone' => 'indigo'],
            ],
            'widgets' => [
                [
                    'title' => 'Incident Trend Placeholder',
                    'description' => 'Reserved for Phase 1 incident metrics once reporting data exists.',
                    'points' => [18, 30, 24, 36, 32, 45],
                ],
                [
                    'title' => 'Action Closure Placeholder',
                    'description' => 'Reserved for CAPA/action tracking after module implementation.',
                    'points' => [55, 48, 62, 58, 71, 76],
                ],
            ],
            'quickLinks' => [
                ['label' => 'Sites', 'route' => 'core.sites.index', 'permission' => 'core.sites.view'],
                ['label' => 'Departments', 'route' => 'core.departments.index', 'permission' => 'core.departments.view'],
                ['label' => 'Files', 'route' => 'core.files.index', 'permission' => 'core.files.view'],
                ['label' => 'Notifications', 'route' => 'core.notifications.index', 'permission' => 'core.notifications.view'],
            ],
            'notificationSummary' => [
                'unread' => CoreNotification::query()
                    ->where('recipient_id', $request->user()?->id)
                    ->whereNull('read_at')
                    ->count(),
            ],
        ]);
    }
}
