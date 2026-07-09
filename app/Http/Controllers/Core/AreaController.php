<?php
namespace App\Http\Controllers\Core;
use App\Http\Controllers\Controller; use App\Http\Requests\Core\AreaRequest; use App\Models\Core\MasterData\Area; use Illuminate\Http\RedirectResponse; use Inertia\Inertia; use Inertia\Response;
class AreaController extends Controller {
 public function index(): Response { $items=Area::query()->when(request('search'), fn($q,$s)=>$q->where('code','like',"%{$s}%")->orWhere('name','like',"%{$s}%"))->orderBy('name')->paginate(10)->withQueryString(); return Inertia::render('Core/Areas/Index',['items'=>$items,'filters'=>request()->only('search')]); }
 public function create(): Response { return Inertia::render('Core/Areas/Form', array_merge(['item'=>null], ['sites'=>\App\Models\Core\MasterData\Site::query()->where('is_active', true)->orderBy('name')->get(['id','name'])])); }
 public function store(AreaRequest $request): RedirectResponse { Area::create($request->validated()+['is_active'=>$request->boolean('is_active', true)]); return redirect()->route('core.areas.index'); }
 public function edit(Area $area): Response { return Inertia::render('Core/Areas/Form', array_merge(['item'=>$area], ['sites'=>\App\Models\Core\MasterData\Site::query()->where('is_active', true)->orderBy('name')->get(['id','name'])])); }
 public function update(AreaRequest $request, Area $area): RedirectResponse { $area->update($request->validated()+['is_active'=>$request->boolean('is_active')]); return redirect()->route('core.areas.index'); }
 public function destroy(Area $area): RedirectResponse { $area->update(['is_active'=>false]); return redirect()->route('core.areas.index'); }
}
