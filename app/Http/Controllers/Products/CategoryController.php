<?php

namespace App\Http\Controllers\Products;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class CategoryController extends Controller
{
    public function index(): View
    {
        $categories = Category::withCount('products')
            ->with('parent')
            ->orderBy('name')
            ->paginate(20);

        $parents = Category::whereNull('parent_id')->orderBy('name')->get(['id', 'name']);

        return view('pages.products.categories', compact('categories', 'parents'));
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'name'      => 'required|string|max:150|unique:categories,name',
            'parent_id' => 'nullable|exists:categories,id',
            'is_active' => 'boolean',
        ]);

        Category::create([
            'name'      => $request->name,
            'slug'      => Str::slug($request->name),
            'parent_id' => $request->parent_id ?: null,
            'is_active' => $request->boolean('is_active', true),
        ]);

        return back()->with('success', 'Catégorie créée.');
    }

    public function update(Request $request, Category $category): RedirectResponse
    {
        $request->validate([
            'name'      => 'required|string|max:150|unique:categories,name,' . $category->id,
            'parent_id' => 'nullable|exists:categories,id',
            'is_active' => 'boolean',
        ]);

        $category->update([
            'name'      => $request->name,
            'slug'      => Str::slug($request->name),
            'parent_id' => $request->parent_id ?: null,
            'is_active' => $request->boolean('is_active', true),
        ]);

        return back()->with('success', 'Catégorie mise à jour.');
    }

    public function destroy(Category $category): RedirectResponse
    {
        if ($category->products()->count() > 0) {
            return back()->with('error', 'Impossible de supprimer : des produits appartiennent à cette catégorie.');
        }

        $category->delete();

        return back()->with('success', 'Catégorie supprimée.');
    }
}
