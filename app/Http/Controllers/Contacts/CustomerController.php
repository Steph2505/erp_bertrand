<?php

namespace App\Http\Controllers\Contacts;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\CustomerGroup;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Throwable;

class CustomerController extends Controller
{
    public function index(Request $request): View|RedirectResponse
    {
        try {
            $customers = Customer::with('group')
                ->withSum(['sales' => fn($q) => $q->where('is_pos', false)], 'total')
                ->when($request->search, fn($q, $s) => $q->where('name', 'like', "%$s%")->orWhere('phone', 'like', "%$s%")->orWhere('email', 'like', "%$s%"))
                ->when($request->group_id, fn($q, $id) => $q->where('customer_group_id', $id))
                ->latest()
                ->paginate(20);

            $groups = CustomerGroup::orderBy('name')->get();
            return view('pages.contacts.customers', compact('customers', 'groups'));
        } catch (Throwable $e) {
            $this->logError($e, 'Erreur lors du chargement de la page des clients');
            return redirect()->route('dashboard')->with('error', 'Une erreur est survenue lors du chargement de la page.');
        }
    }

    public function show(Customer $customer): View|RedirectResponse
    {
        try {
            $sales = $customer->sales()
                ->where('is_pos', false)
                ->withCount('items')
                ->orderByDesc('sale_date')
                ->orderByDesc('id')
                ->paginate(20);

            $stats = $customer->sales()->where('is_pos', false)->selectRaw('
                COUNT(*) as total_count,
                COALESCE(SUM(total), 0) as total_ca,
                COALESCE(SUM(CASE WHEN payment_status = \'paid\' THEN total ELSE 0 END), 0) as total_paid,
                COALESCE(SUM(CASE WHEN payment_status != \'paid\' AND status = \'confirmed\' THEN (total - amount_paid) ELSE 0 END), 0) as total_due
            ')->first();

            // Le CA inclut le montant initial saisi à la création du client
            $stats->total_ca = (float) $stats->total_ca + (float) $customer->opening_balance;

            return view('pages.contacts.customer-show', compact('customer', 'sales', 'stats'));
        } catch (Throwable $e) {
            $this->logError($e, 'Erreur lors du chargement de la fiche client', ['customer_id' => $customer->id]);
            return redirect()->route('customers.index')->with('error', 'Une erreur est survenue lors du chargement de la fiche client.');
        }
    }

    public function store(Request $request): RedirectResponse|JsonResponse
    {
        $data = $request->validate([
            'name'              => 'required|string|max:191',
            'email'             => 'nullable|email|unique:customers,email',
            'phone'             => 'nullable|string|max:30',
            'address'           => 'nullable|string',
            'customer_group_id' => 'nullable|exists:customer_groups,id',
            'opening_balance'   => 'nullable|numeric|min:0',
            'ristourne_percent' => 'nullable|numeric|min:0|max:100',
        ]);

        try {
            $customer = Customer::create(array_merge($data, ['is_active' => true]));
        } catch (Throwable $e) {
            $this->logError($e, 'Erreur lors de la création du client', ['data' => $data]);

            if ($request->wantsJson()) {
                return response()->json(['message' => 'Une erreur est survenue lors de la création du client.'], 500);
            }
            return back()->withInput()->with('error', 'Une erreur est survenue lors de la création du client.');
        }

        if ($request->wantsJson()) {
            return response()->json([
                'success'  => true,
                'customer' => ['id' => $customer->id, 'name' => $customer->name, 'phone' => $customer->phone],
            ]);
        }

        return back()->with('success', 'Client créé avec succès.');
    }

    public function update(Request $request, Customer $customer): RedirectResponse
    {
        // Le montant initial (base du chiffre d'affaires) n'est modifiable qu'à la création du client.
        $data = $request->validate([
            'name'              => 'required|string|max:191',
            'email'             => 'nullable|email|unique:customers,email,' . $customer->id,
            'phone'             => 'nullable|string|max:30',
            'address'           => 'nullable|string',
            'customer_group_id' => 'nullable|exists:customer_groups,id',
            'ristourne_percent' => 'nullable|numeric|min:0|max:100',
            'is_active'         => 'boolean',
        ]);

        try {
            $customer->update($data);
        } catch (Throwable $e) {
            $this->logError($e, 'Erreur lors de la mise à jour du client', ['customer_id' => $customer->id, 'data' => $data]);
            return back()->withInput()->with('error', 'Une erreur est survenue lors de la mise à jour du client.');
        }

        return back()->with('success', 'Client mis à jour.');
    }

    public function destroy(Customer $customer): RedirectResponse
    {
        try {
            $customer->delete();
        } catch (Throwable $e) {
            $this->logError($e, 'Erreur lors de la suppression du client', ['customer_id' => $customer->id]);
            return back()->with('error', 'Une erreur est survenue lors de la suppression du client.');
        }

        return back()->with('success', 'Client supprimé.');
    }
}
