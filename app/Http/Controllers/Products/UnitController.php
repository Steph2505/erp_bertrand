<?php

namespace App\Http\Controllers\Products;

use App\Http\Controllers\Controller;
use App\Models\Unit;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class UnitController extends Controller
{
    public function index(): View
    {
        $units = Unit::withCount('products')->orderBy('name')->paginate(20);
        return view('pages.products.units', compact('units'));
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'name'         => 'required|string|max:100|unique:units,name',
            'abbreviation' => 'required|string|max:20',
        ]);

        Unit::create($request->only('name', 'abbreviation'));

        return back()->with('success', 'Unité créée avec succès.');
    }

    public function update(Request $request, Unit $unit): RedirectResponse
    {
        $request->validate([
            'name'         => 'required|string|max:100|unique:units,name,' . $unit->id,
            'abbreviation' => 'required|string|max:20',
        ]);

        $unit->update($request->only('name', 'abbreviation'));

        return back()->with('success', 'Unité mise à jour.');
    }

    public function destroy(Unit $unit): RedirectResponse
    {
        if ($unit->products()->count() > 0) {
            return back()->with('error', 'Impossible de supprimer : des produits utilisent cette unité.');
        }

        $unit->delete();

        return back()->with('success', 'Unité supprimée.');
    }
}
