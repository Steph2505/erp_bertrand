<?php

namespace App\Http\Controllers\Contacts;

use App\Http\Controllers\Controller;
use App\Models\Supplier;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use App\Helpers\FormatHelper;

class SupplierController extends Controller
{
    public function index(Request $request): View
    {
        $suppliers = Supplier::withSum('purchases', 'total')
            ->when($request->search, fn($q, $s) =>
                $q->where('name', 'like', "%$s%")->orWhere('phone', 'like', "%$s%")->orWhere('email', 'like', "%$s%")
            )
            ->latest()
            ->paginate(20);

        return view('pages.contacts.suppliers', compact('suppliers'));
    }

    public function show(Supplier $supplier): View
    {
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

        Supplier::create(array_merge($data, ['is_active' => true]));
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

        $supplier->update($data);
        return back()->with('success', 'Fournisseur mis à jour.');
    }

    public function destroy(Supplier $supplier): RedirectResponse
    {
        $supplier->delete();
        return back()->with('success', 'Fournisseur supprimé.');
    }
}
