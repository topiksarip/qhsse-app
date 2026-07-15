<?php

declare(strict_types=1);

namespace App\Http\Controllers\Modules\EmergencyPreparedness;

use App\Http\Controllers\Controller;
use App\Http\Requests\Modules\EmergencyPreparedness\StoreEmergencyContactRequest;
use App\Http\Requests\Modules\EmergencyPreparedness\UpdateEmergencyContactRequest;
use App\Models\Core\MasterData\Site;
use App\Models\Modules\EmergencyPreparedness\EmergencyContact;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class EmergencyContactController extends Controller
{
    use AuthorizesRequests;

    public function index(Request $request): Response
    {
        $this->authorize('viewAny', EmergencyContact::class);

        $query = EmergencyContact::query()
            ->with(['site'])
            ->active();

        // Search
        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('role', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        // Filters
        if ($siteId = $request->input('site_id')) {
            $query->where('site_id', $siteId);
        }

        if ($request->has('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        // Scope filtering
        $user = $request->user();
        if (! $user->hasAnyRole(['Super Admin', 'Admin', 'QHSSE Manager', 'Top Management', 'Auditor'])) {
            if ($user->hasRole('QHSSE Officer')) {
                $query->whereIn('site_id', $user->employee->sites->pluck('id'));
            } elseif ($user->hasAnyRole(['Supervisor', 'Department Head', 'Employee / Reporter'])) {
                $query->where('site_id', $user->employee->site_id);
            }
        }

        // Sort
        $sortBy = $request->input('sort_by', 'name');
        $sortOrder = $request->input('sort_order', 'asc');
        $query->orderBy($sortBy, $sortOrder);

        $contacts = $query->paginate(20);

        return Inertia::render('Modules/EmergencyPreparedness/Contacts/Index', [
            'contacts' => $contacts,
            'filters' => $request->only(['search', 'site_id', 'is_active', 'sort_by', 'sort_order']),
            'sites' => Site::where('is_active', true)->orderBy('name')->get(['id', 'name']),
            'can' => [
                'create' => $user->can('emergency.contacts.create'),
                'update' => $user->can('emergency.contacts.update'),
                'delete' => $user->can('emergency.contacts.delete'),
            ],
        ]);
    }

    public function create(): Response
    {
        $this->authorize('create', EmergencyContact::class);

        $sites = Site::where('is_active', true)->get(['id', 'name']);

        return Inertia::render('Modules/EmergencyPreparedness/Contacts/CreateOrEdit', [
            'sites' => $sites,
        ]);
    }

    public function store(StoreEmergencyContactRequest $request): RedirectResponse
    {
        $this->authorize('create', EmergencyContact::class);

        $contact = EmergencyContact::create($request->validated());

        return redirect()->route('emergency.contacts.index')
            ->with('success', "Kontak darurat {$contact->name} berhasil ditambahkan.");
    }

    public function show(EmergencyContact $contact): Response
    {
        $this->authorize('view', $contact);

        $contact->load(['site']);

        return Inertia::render('Modules/EmergencyPreparedness/Contacts/Show', [
            'contact' => $contact,
        ]);
    }

    public function edit(EmergencyContact $contact): Response
    {
        $this->authorize('update', $contact);

        $sites = Site::where('is_active', true)->get(['id', 'name']);

        return Inertia::render('Modules/EmergencyPreparedness/Contacts/CreateOrEdit', [
            'contact' => $contact,
            'sites' => $sites,
        ]);
    }

    public function update(UpdateEmergencyContactRequest $request, EmergencyContact $contact): RedirectResponse
    {
        $this->authorize('update', $contact);

        $contact->update($request->validated());

        return redirect()->route('emergency.contacts.show', $contact)
            ->with('success', 'Kontak darurat berhasil diperbarui.');
    }

    public function destroy(EmergencyContact $contact): RedirectResponse
    {
        $this->authorize('delete', $contact);

        $contactName = $contact->name;

        $contact->delete();

        return redirect()->route('emergency.contacts.index')
            ->with('success', "Kontak darurat {$contactName} berhasil dihapus.");
    }
}
