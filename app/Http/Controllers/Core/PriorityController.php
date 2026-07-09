<?php
namespace App\Http\Controllers\Core;
use App\Http\Controllers\Controller; use App\Http\Requests\Core\PriorityRequest; use App\Models\Core\MasterData\Priority; use Illuminate\Http\RedirectResponse; use Inertia\Inertia; use Inertia\Response;
class PriorityController extends Controller {
 public function index(): Response { $items=Priority::query()->when(request('search'), fn($q,$s)=>$q->where('code','like',"%{$s}%")->orWhere('name','like',"%{$s}%"))->orderBy('id')->paginate(10)->withQueryString(); return Inertia::render('Core/Priorities/Index',['items'=>$items,'filters'=>request()->only('search')]); }
 public function create(): Response { return Inertia::render('Core/Priorities/Form',['item'=>null]); }
 public function store(PriorityRequest $request): RedirectResponse { Priority::create($request->validated()+['is_active'=>$request->boolean('is_active', true)]); return redirect()->route('core.priorities.index'); }
 public function edit(Priority $priority): Response { return Inertia::render('Core/Priorities/Form',['item'=>$priority]); }
 public function update(PriorityRequest $request, Priority $priority): RedirectResponse { $priority->update($request->validated()+['is_active'=>$request->boolean('is_active')]); return redirect()->route('core.priorities.index'); }
 public function destroy(Priority $priority): RedirectResponse { $priority->update(['is_active'=>false]); return redirect()->route('core.priorities.index'); }
}
