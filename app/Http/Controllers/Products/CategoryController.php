<?php

namespace App\Http\Controllers\Products;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Throwable;

class CategoryController extends Controller
{
    public function index(): View|RedirectResponse
    {
        try {
            $categories = Category::withCount('products')
                ->with('parent')
                ->orderBy('name')
                ->paginate(20);

            $parents = Category::whereNull('parent_id')->orderBy('name')->get(['id', 'name']);

            return view('pages.products.categories', compact('categories', 'parents'));
        } catch (Throwable $e) {
            $this->logError($e, 'Erreur lors du chargement des catégories');
            return redirect()->route('dashboard')->with('error', 'Une erreur est survenue lors du chargement de la page.');
        }
    }

    public function store(Request $request): RedirectResponse|JsonResponse
    {
        $request->validate([
            'name'      => 'required|string|max:150|unique:categories,name',
            'parent_id' => 'nullable|exists:categories,id',
            'is_active' => 'boolean',
        ]);

        try {
            $category = Category::create([
                'name'      => $request->name,
                'slug'      => Str::slug($request->name),
                'parent_id' => $request->parent_id ?: null,
                'is_active' => $request->boolean('is_active', true),
            ]);
        } catch (Throwable $e) {
            $this->logError($e, 'Erreur lors de la création de la catégorie', ['name' => $request->name]);

            if ($request->wantsJson()) {
                return response()->json(['message' => 'Une erreur est survenue lors de la création de la catégorie.'], 500);
            }
            return back()->withInput()->with('error', 'Une erreur est survenue lors de la création de la catégorie.');
        }

        if ($request->wantsJson()) {
            return response()->json([
                'success'  => true,
                'category' => ['id' => $category->id, 'name' => $category->name],
            ]);
        }

        return back()->with('success', 'Catégorie créée.');
    }

    public function update(Request $request, Category $category): RedirectResponse
    {
        $request->validate([
            'name'      => 'required|string|max:150|unique:categories,name,' . $category->id,
            'parent_id' => 'nullable|exists:categories,id',
            'is_active' => 'boolean',
        ]);

        try {
            $category->update([
                'name'      => $request->name,
                'slug'      => Str::slug($request->name),
                'parent_id' => $request->parent_id ?: null,
                'is_active' => $request->boolean('is_active', true),
            ]);
        } catch (Throwable $e) {
            $this->logError($e, 'Erreur lors de la mise à jour de la catégorie', ['category_id' => $category->id]);
            return back()->withInput()->with('error', 'Une erreur est survenue lors de la mise à jour de la catégorie.');
        }

        return back()->with('success', 'Catégorie mise à jour.');
    }

    public function destroy(Category $category): RedirectResponse
    {
        if ($category->products()->count() > 0) {
            return back()->with('error', 'Impossible de supprimer : des produits appartiennent à cette catégorie.');
        }

        try {
            $category->delete();
        } catch (Throwable $e) {
            $this->logError($e, 'Erreur lors de la suppression de la catégorie', ['category_id' => $category->id]);
            return back()->with('error', 'Une erreur est survenue lors de la suppression de la catégorie.');
        }

        return back()->with('success', 'Catégorie supprimée.');
    }
}
