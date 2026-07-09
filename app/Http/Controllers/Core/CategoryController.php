<?php
namespace App\Http\Controllers\Core;
use App\Http\Controllers\Controller; use App\Http\Requests\Core\CategoryRequest; use App\Models\Core\MasterData\Category; use Illuminate\Http\RedirectResponse; use Inertia\Inertia; use Inertia\Response;
class CategoryController extends Controller {
 public function index(): Response { $items=Category::query()->when(request('search'), fn($q,$s)=>$q->where('code','like',"%{$s}%")->orWhere('name','like',"%{$s}%"))->orderBy('id')->paginate(10)->withQueryString(); return Inertia::render('Core/Categories/Index',['items'=>$items,'filters'=>request()->only('search')]); }
 public function create(): Response { return Inertia::render('Core/Categories/Form',['item'=>null]); }
 public function store(CategoryRequest $request): RedirectResponse { Category::create($request->validated()+['is_active'=>$request->boolean('is_active', true)]); return redirect()->route('core.categories.index'); }
 public function edit(Category $category): Response { return Inertia::render('Core/Categories/Form',['item'=>$category]); }
 public function update(CategoryRequest $request, Category $category): RedirectResponse { $category->update($request->validated()+['is_active'=>$request->boolean('is_active')]); return redirect()->route('core.categories.index'); }
 public function destroy(Category $category): RedirectResponse { $category->update(['is_active'=>false]); return redirect()->route('core.categories.index'); }
}
