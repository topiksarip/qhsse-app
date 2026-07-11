<?php

declare(strict_types=1);

namespace App\Http\Controllers\Modules\LegalCompliance;

use App\Http\Controllers\Controller;
use App\Http\Requests\Modules\LegalCompliance\CompleteLegalObligationRequest;
use App\Http\Requests\Modules\LegalCompliance\StoreLegalObligationRequest;
use App\Http\Requests\Modules\LegalCompliance\UpdateLegalObligationRequest;
use App\Models\Modules\LegalCompliance\LegalObligation;
use App\Models\Modules\LegalCompliance\LegalRegister;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class LegalObligationController extends Controller
{
    use AuthorizesRequests;

    public function store(StoreLegalObligationRequest $request, LegalRegister $register): RedirectResponse
    {
        $this->authorize('createObligation', $register);

        $validated = $request->validated();
        $validated['legal_register_id'] = $register->id;

        // Auto-calculate next_due if last_completed is provided
        if (isset($validated['last_completed']) && ! isset($validated['next_due'])) {
            $obligation = new LegalObligation($validated);
            $validated['next_due'] = $obligation->calculateNextDue($validated['last_completed']);
        }

        $obligation = LegalObligation::create($validated);

        activity()
            ->performedOn($register)
            ->causedBy($request->user())
            ->withProperties([
                'module_name' => 'legal',
                'reference_id' => $register->id,
                'obligation_id' => $obligation->id,
            ])
            ->log('legal.obligation.created');

        return redirect()->route('legal.registers.show', $register)
            ->with('success', 'Kewajiban berhasil ditambahkan.');
    }

    public function update(UpdateLegalObligationRequest $request, LegalRegister $register, LegalObligation $obligation): RedirectResponse
    {
        $this->authorize('updateObligation', $register);

        if ($obligation->legal_register_id !== $register->id) {
            abort(404);
        }

        $validated = $request->validated();

        // Auto-recalculate next_due if last_completed changed
        if (isset($validated['last_completed']) && $validated['last_completed'] !== $obligation->last_completed) {
            $validated['next_due'] = $obligation->calculateNextDue($validated['last_completed']);
        }

        $obligation->update($validated);

        activity()
            ->performedOn($register)
            ->causedBy($request->user())
            ->withProperties([
                'module_name' => 'legal',
                'reference_id' => $register->id,
                'obligation_id' => $obligation->id,
            ])
            ->log('legal.obligation.updated');

        return redirect()->route('legal.registers.show', $register)
            ->with('success', 'Kewajiban berhasil diperbarui.');
    }

    public function complete(CompleteLegalObligationRequest $request, LegalRegister $register, LegalObligation $obligation): RedirectResponse
    {
        $this->authorize('updateObligation', $register);

        if ($obligation->legal_register_id !== $register->id) {
            abort(404);
        }

        if ($obligation->status !== 'pending') {
            return redirect()->route('legal.registers.show', $register)
                ->with('error', 'Hanya kewajiban dengan status "pending" yang dapat diselesaikan.');
        }

        $validated = $request->validated();

        // Calculate next_due
        $nextDue = $obligation->calculateNextDue($validated['last_completed']);

        $obligation->update([
            'last_completed' => $validated['last_completed'],
            'next_due' => $nextDue,
            'evidence_file_id' => $validated['evidence_file_id'],
            'status' => 'completed',
        ]);

        activity()
            ->performedOn($register)
            ->causedBy($request->user())
            ->withProperties([
                'module_name' => 'legal',
                'reference_id' => $register->id,
                'obligation_id' => $obligation->id,
                'last_completed' => $validated['last_completed'],
                'next_due' => $nextDue,
            ])
            ->log('legal.obligation.completed');

        return redirect()->route('legal.registers.show', $register)
            ->with('success', 'Kewajiban berhasil diselesaikan. Next due: ' . $nextDue);
    }

    public function destroy(LegalRegister $register, LegalObligation $obligation): RedirectResponse
    {
        $this->authorize('updateObligation', $register);

        if ($obligation->legal_register_id !== $register->id) {
            abort(404);
        }

        activity()
            ->performedOn($register)
            ->causedBy(request()->user())
            ->withProperties([
                'module_name' => 'legal',
                'reference_id' => $register->id,
                'obligation_id' => $obligation->id,
            ])
            ->log('legal.obligation.deleted');

        $obligation->delete();

        return redirect()->route('legal.registers.show', $register)
            ->with('success', 'Kewajiban berhasil dihapus.');
    }
}
