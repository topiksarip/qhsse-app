<?php

namespace App\Http\Controllers\Core;

use App\Http\Controllers\Controller;
use App\Models\Core\Audit\AuditLog;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AuditLogController extends Controller
{
    public function index(Request $request): Response
    {
        $logs = AuditLog::query()
            ->with('actor:id,name,email')
            ->when($request->string('search')->toString(), function ($query, string $search): void {
                $query->where(function ($query) use ($search): void {
                    $query->where('event', 'like', "%{$search}%")
                        ->orWhere('auditable_type', 'like', "%{$search}%")
                        ->orWhere('module_name', 'like', "%{$search}%")
                        ->orWhere('actor_name', 'like', "%{$search}%");
                });
            })
            ->when($request->string('event')->toString(), fn ($query, string $event) => $query->where('event', $event))
            ->when($request->string('module_name')->toString(), fn ($query, string $module) => $query->where('module_name', $module))
            ->when($request->string('auditable_type')->toString(), fn ($query, string $type) => $query->where('auditable_type', $type))
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return Inertia::render('Core/Audit/Index', [
            'logs' => $logs,
            'filters' => $request->only(['search', 'event', 'module_name', 'auditable_type']),
        ]);
    }

    public function show(AuditLog $auditLog): Response
    {
        $auditLog->load('actor:id,name,email');

        return Inertia::render('Core/Audit/Show', [
            'log' => $auditLog,
        ]);
    }
}
