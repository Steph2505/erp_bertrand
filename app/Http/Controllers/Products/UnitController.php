<?php

namespace App\Http\Controllers\Products;

use App\Http\Controllers\Controller;
use App\Models\Unit;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Throwable;

class UnitController extends Controller
{
    public function index(): View|RedirectResponse
    {
        try {
            $units = Unit::withCount('products')->orderBy('name')->paginate(20);
            return view('pages.products.units', compact('units'));
        } catch (Throwable $e) {
            $this->logError($e, 'Erreur lors du chargement des unités');
            return redirect()->route('dashboard')->with('error', 'Une erreur est survenue lors du chargement de la page.');
        }
    }

    public function store(Request $request): RedirectResponse|JsonResponse
    {
        $request->validate([
            'name'         => 'required|string|max:100|unique:units,name',
            'abbreviation' => 'required|string|max:20',
        ]);

        try {
            $unit = Unit::create($request->only('name', 'abbreviation'));
        } catch (Throwable $e) {
            $this->logError($e, 'Erreur lors de la création de l\'unité', ['name' => $request->name]);

            if ($request->wantsJson()) {
                return response()->json(['message' => 'Une erreur est survenue lors de la création de l\'unité.'], 500);
            }
            return back()->withInput()->with('error', 'Une erreur est survenue lors de la création de l\'unité.');
        }

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'unit'    => ['id' => $unit->id, 'name' => $unit->name, 'abbreviation' => $unit->abbreviation],
            ]);
        }

        return back()->with('success', 'Unité créée avec succès.');
    }

    public function update(Request $request, Unit $unit): RedirectResponse
    {
        $request->validate([
            'name'         => 'required|string|max:100|unique:units,name,' . $unit->id,
            'abbreviation' => 'required|string|max:20',
        ]);

        try {
            $unit->update($request->only('name', 'abbreviation'));
        } catch (Throwable $e) {
            $this->logError($e, 'Erreur lors de la mise à jour de l\'unité', ['unit_id' => $unit->id]);
            return back()->withInput()->with('error', 'Une erreur est survenue lors de la mise à jour de l\'unité.');
        }

        return back()->with('success', 'Unité mise à jour.');
    }

    public function destroy(Unit $unit): RedirectResponse
    {
        if ($unit->products()->count() > 0) {
            return back()->with('error', 'Impossible de supprimer : des produits utilisent cette unité.');
        }

        try {
            $unit->delete();
        } catch (Throwable $e) {
            $this->logError($e, 'Erreur lors de la suppression de l\'unité', ['unit_id' => $unit->id]);
            return back()->with('error', 'Une erreur est survenue lors de la suppression de l\'unité.');
        }

        return back()->with('success', 'Unité supprimée.');
    }
}
