<?php

namespace App\Http\Controllers\Contacts;

use App\Http\Controllers\Controller;
use App\Models\Supplier;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use App\Helpers\FormatHelper;
use Throwable;

class SupplierController extends Controller
{
    public function index(Request $request): View|RedirectResponse
    {
        try {
            return view('pages.contacts.suppliers');
        } catch (Throwable $e) {
            $this->logError($e, 'Erreur lors du chargement de la page des fournisseurs');
            return redirect()->route('dashboard')->with('error', 'Une erreur est survenue lors du chargement de la page.');
        }
    }

    public function apiIndex(Request $request): JsonResponse
    {
        try {
            $paginator = Supplier::withSum('purchases', 'total')
                ->when($request->search, fn($q, $s) =>
                    $q->where('name', 'like', "%$s%")->orWhere('phone', 'like', "%$s%")->orWhere('email', 'like', "%$s%")
                )
                ->latest()
                ->paginate(20, ['*'], 'page', $request->integer('page', 1));

            return response()->json([
                'data' => $paginator->getCollection()->map(fn($s) => [
                    'id'              => $s->id,
                    'name'            => $s->name,
                    'company'         => $s->company,
                    'phone'           => $s->phone,
                    'email'           => $s->email,
                    'address'         => $s->address,
                    'opening_balance' => (float) $s->opening_balance,
                    'is_active'       => (bool) $s->is_active,
                    'total_purchases' => FormatHelper::money((float) ($s->purchases_sum_total ?? 0)),
                    'show_url'        => route('suppliers.show', $s->id),
                    'destroy_url'     => route('suppliers.destroy', $s->id),
                ]),
                'total'        => $paginator->total(),
                'per_page'     => $paginator->perPage(),
                'current_page' => $paginator->currentPage(),
                'last_page'    => $paginator->lastPage(),
                'from'         => $paginator->firstItem() ?? 0,
                'to'           => $paginator->lastItem() ?? 0,
            ]);
        } catch (Throwable $e) {
            $this->logError($e, 'Erreur lors du chargement des fournisseurs', ['filters' => $request->all()]);
            return response()->json(['message' => 'Impossible de charger les fournisseurs.'], 500);
        }
    }

    public function show(Supplier $supplier): View|RedirectResponse
    {
        try {
            $purchases = $supplier->purchases()
                ->withCount('items')
                ->orderByDesc('purchase_date')
                ->orderByDesc('id')
                ->paginate(20);

            $stats = $supplier->purchases()->selectRaw('
                COUNT(*) as total_count,
                COALESCE(SUM(total), 0) as total_achats,
                COALESCE(SUM(amount_paid), 0) as total_paid,
                COALESCE(SUM(total - amount_paid), 0) as total_due
            ')->first();

            return view('pages.contacts.supplier-show', compact('supplier', 'purchases', 'stats'));
        } catch (Throwable $e) {
            $this->logError($e, 'Erreur lors du chargement de la fiche fournisseur', ['supplier_id' => $supplier->id]);
            return redirect()->route('suppliers.index')->with('error', 'Une erreur est survenue lors du chargement de la fiche fournisseur.');
        }
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name'            => 'required|string|max:191',
            'company'         => 'nullable|string|max:191',
            'email'           => 'nullable|email|unique:suppliers,email',
            'phone'           => 'nullable|string|max:30',
            'address'         => 'nullable|string',
            'opening_balance' => 'nullable|numeric|min:0',
        ]);

        try {
            Supplier::create(array_merge($data, ['is_active' => true]));
        } catch (Throwable $e) {
            $this->logError($e, 'Erreur lors de la création du fournisseur', ['data' => $data]);
            return back()->withInput()->with('error', 'Une erreur est survenue lors de la création du fournisseur.');
        }

        return back()->with('success', 'Fournisseur créé avec succès.');
    }

    public function update(Request $request, Supplier $supplier): RedirectResponse
    {
        $data = $request->validate([
            'name'            => 'required|string|max:191',
            'company'         => 'nullable|string|max:191',
            'email'           => 'nullable|email|unique:suppliers,email,' . $supplier->id,
            'phone'           => 'nullable|string|max:30',
            'address'         => 'nullable|string',
            'opening_balance' => 'nullable|numeric|min:0',
            'is_active'       => 'boolean',
        ]);

        try {
            $supplier->update($data);
        } catch (Throwable $e) {
            $this->logError($e, 'Erreur lors de la mise à jour du fournisseur', ['supplier_id' => $supplier->id, 'data' => $data]);
            return back()->withInput()->with('error', 'Une erreur est survenue lors de la mise à jour du fournisseur.');
        }

        return back()->with('success', 'Fournisseur mis à jour.');
    }

    public function destroy(Supplier $supplier): RedirectResponse
    {
        if ($supplier->purchases()->exists()) {
            return back()->with('error', 'Impossible de supprimer ce fournisseur : il est déjà associé à des achats.');
        }

        try {
            $supplier->delete();
        } catch (Throwable $e) {
            $this->logError($e, 'Erreur lors de la suppression du fournisseur', ['supplier_id' => $supplier->id]);
            return back()->with('error', 'Une erreur est survenue lors de la suppression du fournisseur.');
        }

        return back()->with('success', 'Fournisseur supprimé.');
    }
}
