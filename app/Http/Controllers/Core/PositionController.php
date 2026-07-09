<?php
namespace App\Http\Controllers\Core;
use App\Http\Controllers\Controller; use App\Http\Requests\Core\PositionRequest; use App\Models\Core\MasterData\Position; use Illuminate\Http\RedirectResponse; use Inertia\Inertia; use Inertia\Response;
class PositionController extends Controller {
 public function index(): Response { $items=Position::query()->when(request('search'), fn($q,$s)=>$q->where('code','like',"%{$s}%")->orWhere('name','like',"%{$s}%"))->orderBy('name')->paginate(10)->withQueryString(); return Inertia::render('Core/Positions/Index',['items'=>$items,'filters'=>request()->only('search')]); }
 public function create(): Response { return Inertia::render('Core/Positions/Form', array_merge(['item'=>null], ['departments'=>\App\Models\Core\MasterData\Department::query()->where('is_active', true)->orderBy('name')->get(['id','name'])])); }
 public function store(PositionRequest $request): RedirectResponse { Position::create($request->validated()+['is_active'=>$request->boolean('is_active', true)]); return redirect()->route('core.positions.index'); }
 public function edit(Position $position): Response { return Inertia::render('Core/Positions/Form', array_merge(['item'=>$position], ['departments'=>\App\Models\Core\MasterData\Department::query()->where('is_active', true)->orderBy('name')->get(['id','name'])])); }
 public function update(PositionRequest $request, Position $position): RedirectResponse { $position->update($request->validated()+['is_active'=>$request->boolean('is_active')]); return redirect()->route('core.positions.index'); }
 public function destroy(Position $position): RedirectResponse { $position->update(['is_active'=>false]); return redirect()->route('core.positions.index'); }
}
