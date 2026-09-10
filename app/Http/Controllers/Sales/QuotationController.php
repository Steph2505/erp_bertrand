<?php

namespace App\Http\Controllers\Sales;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Product;
use App\Models\Quotation;
use App\Models\QuotationItem;
use App\Models\Sale;
use App\Models\SaleItem;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Throwable;

class QuotationController extends Controller
{
    public function index(): View|RedirectResponse
    {
        try {
            $customers = Customer::orderBy('name')->get(['id', 'name']);
            return view('pages.quotations.index', compact('customers'));
        } catch (Throwable $e) {
            $this->logError($e, 'Erreur lors du chargement de la page des devis');
            return redirect()->route('dashboard')->with('error', 'Une erreur est survenue lors du chargement de la page.');
        }
    }

    public function apiIndex(Request $request): JsonResponse
    {
        try {
            $paginator = Quotation::with(['customer'])
                ->when($request->status,      fn($q, $v)  => $q->where('status', $v))
                ->when($request->customer_id, fn($q, $id) => $q->where('customer_id', $id))
                ->orderByDesc('quotation_date')
                ->paginate(10, ['*'], 'page', $request->integer('page', 1));

            return response()->json([
                'data' => $paginator->getCollection()->map(fn($q) => [
                    'id'             => $q->id,
                    'reference'      => $q->reference,
                    'customer'       => $q->customer?->name ?? 'Client comptoir',
                    'quotation_date' => \App\Helpers\FormatHelper::date($q->quotation_date),
                    'expiry_date'    => $q->expiry_date ? \App\Helpers\FormatHelper::date($q->expiry_date) : null,
                    'expiry_past'    => $q->expiry_date && $q->expiry_date->isPast(),
                    'total'          => \App\Helpers\FormatHelper::money($q->total),
                    'status'         => $q->status,
                    'status_label'   => Quotation::statusLabel($q->status),
                    'status_class'   => Quotation::statusBadgeClass($q->status),
                    'show_url'       => route('quotations.show', $q->id),
                    'convert_url'    => $q->status === 'accepted' ? route('quotations.convert', $q->id) : null,
                    'destroy_url'    => route('quotations.destroy', $q->id),
                ]),
                'total'        => $paginator->total(),
                'per_page'     => $paginator->perPage(),
                'current_page' => $paginator->currentPage(),
                'last_page'    => $paginator->lastPage(),
                'from'         => $paginator->firstItem() ?? 0,
                'to'           => $paginator->lastItem() ?? 0,
            ]);
        } catch (Throwable $e) {
            $this->logError($e, 'Erreur lors du chargement des devis', ['filters' => $request->only(['status', 'customer_id', 'page'])]);
            return response()->json(['message' => 'Impossible de charger les devis.'], 500);
        }
    }

    public function create(): View|RedirectResponse
    {
        try {
            $customers = Customer::orderBy('name')->get(['id', 'name']);
            $products  = Product::where('is_active', true)->orderBy('name')
                ->get(['id', 'name', 'variation', 'selling_price'])
                ->map(fn($p) => ['id' => $p->id, 'name' => $p->display_name, 'selling_price' => $p->selling_price]);

            return view('pages.quotations.create', compact('customers', 'products'));
        } catch (Throwable $e) {
            $this->logError($e, 'Erreur lors du chargement du formulaire de devis');
            return redirect()->route('quotations.index')->with('error', 'Une erreur est survenue lors du chargement de la page.');
        }
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'customer_id'    => 'nullable|exists:customers,id',
            'quotation_date' => 'required|date',
            'expiry_date'    => 'nullable|date|after_or_equal:quotation_date',
            'note'           => 'nullable|string',
            'items'          => 'required|array|min:1',
            'items.*.name'   => 'required|string',
            'items.*.qty'    => 'required|numeric|min:0.01',
            'items.*.price'  => 'required|numeric|min:0',
        ]);

        try {
            DB::transaction(function () use ($request) {
                $ref = 'DEV-' . date('Ymd') . '-' . str_pad(
                    Quotation::whereDate('created_at', today())->count() + 1, 4, '0', STR_PAD_LEFT
                );

                $subtotal = collect($request->items)->sum(fn($i) => $i['qty'] * $i['price']);
                $discount = (float) ($request->discount ?? 0);

                $quotation = Quotation::create([
                    'reference'      => $ref,
                    'customer_id'    => $request->customer_id,
                    'quotation_date' => $request->quotation_date,
                    'expiry_date'    => $request->expiry_date,
                    'status'         => 'draft',
                    'subtotal'       => $subtotal,
                    'discount'       => $discount,
                    'tax_amount'     => 0,
                    'total'          => $subtotal - $discount,
                    'note'           => $request->note,
                    'created_by'     => auth()->id(),
                ]);

                foreach ($request->items as $item) {
                    QuotationItem::create([
                        'quotation_id' => $quotation->id,
                        'product_id'   => $item['product_id'] ?? null,
                        'item_name'    => $item['name'],
                        'item_type'    => $item['type'] ?? 'product',
                        'quantity'     => $item['qty'],
                        'unit_price'   => $item['price'],
                        'subtotal'     => $item['qty'] * $item['price'],
                    ]);
                }
            });
        } catch (Throwable $e) {
            $this->logError($e, 'Erreur lors de la création du devis', ['request' => $request->except('_token')]);
            return back()->withInput()->with('error', 'Une erreur est survenue lors de la création du devis.');
        }

        return redirect()->route('quotations.index')->with('success', 'Devis créé avec succès.');
    }

    public function show(Quotation $quotation): View|RedirectResponse
    {
        try {
            $quotation->load(['customer', 'items.product', 'createdBy']);
            return view('pages.quotations.show', compact('quotation'));
        } catch (Throwable $e) {
            $this->logError($e, 'Erreur lors du chargement du devis', ['quotation_id' => $quotation->id]);
            return redirect()->route('quotations.index')->with('error', 'Une erreur est survenue lors du chargement du devis.');
        }
    }

    public function updateStatus(Request $request, Quotation $quotation): RedirectResponse
    {
        $request->validate(['status' => 'required|in:draft,sent,accepted,rejected,expired']);

        try {
            $quotation->update(['status' => $request->status]);
        } catch (Throwable $e) {
            $this->logError($e, 'Erreur lors de la mise à jour du statut du devis', ['quotation_id' => $quotation->id]);
            return back()->with('error', 'Une erreur est survenue lors de la mise à jour du statut.');
        }

        return back()->with('success', 'Statut du devis mis à jour.');
    }

    public function convertToSale(Quotation $quotation): RedirectResponse
    {
        if ($quotation->status !== 'accepted') {
            return back()->with('error', 'Seul un devis accepté peut être converti en vente.');
        }

        try {
            $quotation->load('items.product');

            DB::transaction(function () use ($quotation) {
                $ref = 'FAC-' . date('Ymd') . '-' . str_pad(
                    Sale::whereDate('created_at', today())->count() + 1, 4, '0', STR_PAD_LEFT
                );

                $sale = Sale::create([
                    'reference'      => $ref,
                    'customer_id'    => $quotation->customer_id,
                    'sale_date'      => today(),
                    'status'         => 'completed',
                    'payment_status' => 'pending',
                    'subtotal'       => $quotation->subtotal,
                    'discount'       => $quotation->discount,
                    'tax_amount'     => $quotation->tax_amount,
                    'total'          => $quotation->total,
                    'amount_paid'    => 0,
                    'is_pos'         => false,
                    'note'           => 'Converti du devis ' . $quotation->reference,
                    'created_by'     => auth()->id(),
                ]);

                foreach ($quotation->items as $item) {
                    SaleItem::create([
                        'sale_id'    => $sale->id,
                        'product_id' => $item->product_id,
                        'item_name'  => $item->item_name,
                        'item_type'  => $item->item_type,
                        'quantity'   => $item->quantity,
                        'unit_price' => $item->unit_price,
                        'unit_cost'  => $item->product?->buying_price ?? 0,
                        'subtotal'   => $item->subtotal,
                    ]);
                }
            });
        } catch (Throwable $e) {
            $this->logError($e, 'Erreur lors de la conversion du devis en vente', ['quotation_id' => $quotation->id]);
            return back()->with('error', 'Une erreur est survenue lors de la conversion du devis en vente.');
        }

        return redirect()->route('sales.index')->with('success', 'Devis converti en vente avec succès.');
    }

    public function destroy(Quotation $quotation): RedirectResponse
    {
        try {
            $quotation->delete();
        } catch (Throwable $e) {
            $this->logError($e, 'Erreur lors de la suppression du devis', ['quotation_id' => $quotation->id]);
            return back()->with('error', 'Une erreur est survenue lors de la suppression du devis.');
        }

        return redirect()->route('quotations.index')->with('success', 'Devis supprimé.');
    }
}
