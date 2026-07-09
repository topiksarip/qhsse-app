<?php
namespace App\Http\Controllers\Core;
use App\Http\Controllers\Controller; use App\Http\Requests\Core\SeverityRequest; use App\Models\Core\MasterData\Severity; use Illuminate\Http\RedirectResponse; use Inertia\Inertia; use Inertia\Response;
class SeverityController extends Controller {
 public function index(): Response { $items=Severity::query()->when(request('search'), fn($q,$s)=>$q->where('code','like',"%{$s}%")->orWhere('name','like',"%{$s}%"))->orderBy('id')->paginate(10)->withQueryString(); return Inertia::render('Core/Severities/Index',['items'=>$items,'filters'=>request()->only('search')]); }
 public function create(): Response { return Inertia::render('Core/Severities/Form',['item'=>null]); }
 public function store(SeverityRequest $request): RedirectResponse { Severity::create($request->validated()+['is_active'=>$request->boolean('is_active', true)]); return redirect()->route('core.severities.index'); }
 public function edit(Severity $severity): Response { return Inertia::render('Core/Severities/Form',['item'=>$severity]); }
 public function update(SeverityRequest $request, Severity $severity): RedirectResponse { $severity->update($request->validated()+['is_active'=>$request->boolean('is_active')]); return redirect()->route('core.severities.index'); }
 public function destroy(Severity $severity): RedirectResponse { $severity->update(['is_active'=>false]); return redirect()->route('core.severities.index'); }
}
