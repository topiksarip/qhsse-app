<?php

namespace App\Http\Controllers\Modules\Security;

use App\Core\Activity\ActivityService;
use App\Core\Audit\AuditService;
use App\Core\Notifications\NotificationService;
use App\Http\Controllers\Controller;
use App\Http\Requests\Modules\Security\StorePatrolResultRequest;
use App\Models\Modules\Security\PatrolChecklist;
use App\Models\Modules\Security\PatrolResult;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PatrolResultController extends Controller
{
    public function __construct(
        private AuditService $audit,
        private ActivityService $activity,
        private NotificationService $notifications,
    ) {}

    public function store(
        StorePatrolResultRequest $request,
        PatrolChecklist $patrol,
        PatrolResult $result,
    ): RedirectResponse {
        $this->authorize('execute', $patrol);
        if ($result->patrol_checklist_id !== $patrol->id) {
            abort(404);
        }
        if (! $patrol->isInProgress()) {
            throw ValidationException::withMessages(['status' => 'Hasil hanya dapat diisi saat patroli In Progress.']);
        }

        DB::transaction(function () use ($request, $patrol, $result) {
            $lockedPatrol = PatrolChecklist::query()->lockForUpdate()->findOrFail($patrol->id);
            if (! $lockedPatrol->isInProgress()) {
                throw ValidationException::withMessages(['status' => 'Hasil hanya dapat diisi saat patroli In Progress.']);
            }
            $lockedResult = PatrolResult::query()->lockForUpdate()->findOrFail($result->id);
            $old = $lockedResult->getAttributes();
            $lockedResult->update([
                ...$request->validated(),
                'findings' => $request->input('findings') ?: null,
                'checked_at' => now(),
            ]);

            $this->audit->log(
                'security.patrol.result_recorded',
                $lockedResult,
                $old,
                $lockedResult->getAttributes(),
                $request->user(),
                'security_patrol',
                $patrol->id,
            );
            $this->activity->log(
                'security_patrol',
                $patrol->id,
                'result_recorded',
                "Checkpoint {$lockedResult->checkpoint} dicatat sebagai {$lockedResult->getResultLabel()}.",
                $request->user(),
            );

            if ($lockedResult->hasIssue() && ($old['result'] ?? null) !== 'issue') {
                $this->notifyIssue($lockedPatrol, $lockedResult, $request->user());
            }
        });

        return back()->with('success', "Hasil checkpoint {$result->checkpoint} tersimpan.");
    }

    private function notifyIssue(PatrolChecklist $patrol, PatrolResult $result, User $actor): void
    {
        $recipients = User::role(['QHSSE Officer', 'QHSSE Manager'])
            ->where('id', '!=', $actor->id)
            ->where(function (Builder $query) use ($patrol) {
                $query->whereHas('roles', fn (Builder $role) => $role->where('name', 'QHSSE Manager'))
                    ->orWhereHas('employee', fn (Builder $employee) => $employee->where('site_id', $patrol->site_id));
            })->get();

        $this->notifications->notifyMany($recipients, 'security.patrol.issue_found', [
            'patrol_number' => $patrol->patrol_number,
            'checkpoint' => $result->checkpoint,
            'findings' => $result->findings,
        ], $actor, 'security_patrol', $patrol->id, route('security.patrols.show', $patrol));
    }
}
