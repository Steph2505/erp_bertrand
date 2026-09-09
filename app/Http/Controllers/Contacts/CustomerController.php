<?php

namespace App\Http\Controllers\Contacts;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\CustomerGroup;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CustomerController extends Controller
{
    public function index(Request $request): View
    {
        $customers = Customer::with('group')
            ->withSum(['sales' => fn($q) => $q->where('is_pos', false)], 'total')
            ->when($request->search, fn($q, $s) => $q->where('name', 'like', "%$s%")->orWhere('phone', 'like', "%$s%")->orWhere('email', 'like', "%$s%"))
            ->when($request->group_id, fn($q, $id) => $q->where('customer_group_id', $id))
            ->latest()
            ->paginate(20);

        $groups = CustomerGroup::orderBy('name')->get();
        return view('pages.contacts.customers', compact('customers', 'groups'));
    }

    public function show(Customer $customer): View
    {
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

        return view('pages.contacts.customer-show', compact('customer', 'sales', 'stats'));
    }

    public function store(Request $request): RedirectResponse|\Illuminate\Http\JsonResponse
    {
        $data = $request->validate([
            'name'              => 'required|string|max:191',
            'email'             => 'nullable|email|unique:customers,email',
            'phone'             => 'nullable|string|max:30',
            'address'           => 'nullable|string',
            'customer_group_id' => 'nullable|exists:customer_groups,id',
            'opening_balance'   => 'nullable|numeric|min:0',
        ]);

        $customer = Customer::create(array_merge($data, ['is_active' => true]));

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
        $data = $request->validate([
            'name'              => 'required|string|max:191',
            'email'             => 'nullable|email|unique:customers,email,' . $customer->id,
            'phone'             => 'nullable|string|max:30',
            'address'           => 'nullable|string',
            'customer_group_id' => 'nullable|exists:customer_groups,id',
            'opening_balance'   => 'nullable|numeric|min:0',
            'is_active'         => 'boolean',
        ]);

        $customer->update($data);
        return back()->with('success', 'Client mis à jour.');
    }

    public function destroy(Customer $customer): RedirectResponse
    {
        $customer->delete();
        return back()->with('success', 'Client supprimé.');
    }
}
