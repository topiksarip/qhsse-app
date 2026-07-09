<?php
namespace App\Http\Controllers\Core;
use App\Http\Controllers\Controller; use App\Http\Requests\Core\RiskMatrixLevelRequest; use App\Models\Core\MasterData\RiskMatrixLevel; use Illuminate\Http\RedirectResponse; use Inertia\Inertia; use Inertia\Response;
class RiskMatrixLevelController extends Controller {
 public function index(): Response { $items=RiskMatrixLevel::query()->when(request('search'), fn($q,$s)=>$q->where('code','like',"%{$s}%")->orWhere('name','like',"%{$s}%"))->orderBy('id')->paginate(10)->withQueryString(); return Inertia::render('Core/RiskMatrixLevels/Index',['items'=>$items,'filters'=>request()->only('search')]); }
 public function create(): Response { return Inertia::render('Core/RiskMatrixLevels/Form',['item'=>null]); }
 public function store(RiskMatrixLevelRequest $request): RedirectResponse { RiskMatrixLevel::create($request->validated()+['is_active'=>$request->boolean('is_active', true)]); return redirect()->route('core.risk-matrix.index'); }
 public function edit(RiskMatrixLevel $riskMatrixLevel): Response { return Inertia::render('Core/RiskMatrixLevels/Form',['item'=>$riskMatrixLevel]); }
 public function update(RiskMatrixLevelRequest $request, RiskMatrixLevel $riskMatrixLevel): RedirectResponse { $riskMatrixLevel->update($request->validated()+['is_active'=>$request->boolean('is_active')]); return redirect()->route('core.risk-matrix.index'); }
 public function destroy(RiskMatrixLevel $riskMatrixLevel): RedirectResponse { $riskMatrixLevel->update(['is_active'=>false]); return redirect()->route('core.risk-matrix.index'); }
}
