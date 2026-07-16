<?php

namespace App\Http\Controllers\Modules\Apd;

use App\Http\Controllers\Controller;
use App\Http\Requests\Modules\Apd\StoreApdRequirementRequest;
use App\Models\Modules\Apd\ApdRequirement;
use App\Models\Modules\RiskManagement\RiskRegister;
use App\Modules\Apd\ApdAccess;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ApdRequirementController extends Controller
{
    public function __construct(private readonly ApdAccess $apdAccess) {}

    /**
     * Attach an APD requirement to a risk register.
     */
    public function store(StoreApdRequirementRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $riskRegister = RiskRegister::findOrFail($validated['risk_register_id']);

        // Location scope check via the linked catalog (catalog is scoped by ApdAccess).
        $catalog = \App\Models\Modules\Apd\ApdCatalog::findOrFail($validated['apd_catalog_id']);
        abort_unless($this->apdAccess->canUseLocation($request->user(), (int) $catalog->site_id, $catalog->department_id), 403);

        DB::transaction(function () use ($validated, $request) {
            ApdRequirement::create([
                'risk_register_id' => $validated['risk_register_id'],
                'apd_catalog_id' => $validated['apd_catalog_id'],
                'quantity' => $validated['quantity'],
                'notes' => $validated['notes'] ?? null,
                'created_by' => $request->user()->id,
                'updated_by' => $request->user()->id,
            ]);
        });

        return back()->with('success', 'Kebutuhan APD ditambahkan ke risk register.');
    }

    public function destroy(Request $request, ApdRequirement $requirement): RedirectResponse
    {
        $this->authorize('delete', $requirement);
        $request->user()->can('apd.requirements.manage') || abort(403);

        $requirement->delete();

        return back()->with('success', 'Kebutuhan APD dihapus.');
    }
}
