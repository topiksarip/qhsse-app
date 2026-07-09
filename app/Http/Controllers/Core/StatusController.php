<?php
namespace App\Http\Controllers\Core;
use App\Http\Controllers\Controller; use App\Http\Requests\Core\StatusRequest; use App\Models\Core\MasterData\Status; use Illuminate\Http\RedirectResponse; use Inertia\Inertia; use Inertia\Response;
class StatusController extends Controller {
 public function index(): Response { $items=Status::query()->when(request('search'), fn($q,$s)=>$q->where('code','like',"%{$s}%")->orWhere('name','like',"%{$s}%"))->orderBy('id')->paginate(10)->withQueryString(); return Inertia::render('Core/Statuses/Index',['items'=>$items,'filters'=>request()->only('search')]); }
 public function create(): Response { return Inertia::render('Core/Statuses/Form',['item'=>null]); }
 public function store(StatusRequest $request): RedirectResponse { Status::create($request->validated()+['is_active'=>$request->boolean('is_active', true)]); return redirect()->route('core.statuses.index'); }
 public function edit(Status $status): Response { return Inertia::render('Core/Statuses/Form',['item'=>$status]); }
 public function update(StatusRequest $request, Status $status): RedirectResponse { $status->update($request->validated()+['is_active'=>$request->boolean('is_active')]); return redirect()->route('core.statuses.index'); }
 public function destroy(Status $status): RedirectResponse { $status->update(['is_active'=>false]); return redirect()->route('core.statuses.index'); }
}
