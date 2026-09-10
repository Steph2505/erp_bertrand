<?php

namespace App\Http\Controllers\Sales;

use App\Http\Controllers\Controller;
use App\Models\Sale;
use App\Models\SaleReturn;
use App\Repositories\ProductRepository;
use App\Services\PackStockService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Throwable;

class SaleReturnController extends Controller
{
    public function __construct(
        private readonly ProductRepository $productRepo,
        private readonly PackStockService  $stockService,
    ) {}
    public function index(): View|RedirectResponse
    {
        try {
            $returns = SaleReturn::with(['sale', 'createdBy'])
                ->orderByDesc('return_date')
                ->paginate(20);

            return view('pages.sales.returns', compact('returns'));
        } catch (Throwable $e) {
            $this->logError($e, 'Erreur lors du chargement de la page des retours de vente');
            return redirect()->route('dashboard')->with('error', 'Une erreur est survenue lors du chargement de la page.');
        }
    }

    public function create(Request $request): View|RedirectResponse
    {
        try {
            $sale = null;
            if ($request->sale_id) {
                $sale = Sale::with(['customer', 'items'])->findOrFail($request->sale_id);
            }

            $sales = Sale::with('customer')
                ->where('status', 'completed')
                ->orderByDesc('sale_date')
                ->get(['id', 'reference', 'customer_id', 'total', 'sale_date']);

            return view('pages.sales.return-create', compact('sale', 'sales'));
        } catch (Throwable $e) {
            $this->logError($e, 'Erreur lors du chargement du formulaire de retour de vente', ['sale_id' => $request->sale_id]);
            return redirect()->route('sale-returns.index')->with('error', 'Une erreur est survenue lors du chargement de la page.');
        }
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'sale_id'       => 'required|exists:sales,id',
            'return_date'   => 'required|date',
            'total'         => 'required|numeric|min:0.01',
            'reason'        => 'nullable|string|max:500',
            'restore_stock' => 'boolean',
        ]);

        $sale = Sale::with('items.product')->findOrFail($request->sale_id);

        if ((float) $request->total > (float) $sale->total) {
            return back()->withErrors(['total' => 'Le montant retourné ne peut pas dépasser le total de la vente.'])->withInput();
        }

        try {
            DB::transaction(function () use ($request, $sale) {
                $ref = 'RET-' . date('Ymd') . '-' . str_pad(
                    SaleReturn::whereDate('created_at', today())->count() + 1, 4, '0', STR_PAD_LEFT
                );

                SaleReturn::create([
                    'reference'   => $ref,
                    'sale_id'     => $request->sale_id,
                    'return_date' => $request->return_date,
                    'total'       => $request->total,
                    'reason'      => $request->reason,
                    'created_by'  => auth()->id(),
                ]);

                // Restauration du stock si demandée (retour complet uniquement)
                if ($request->boolean('restore_stock') && (float) $request->total >= (float) $sale->total) {
                    foreach ($sale->items as $item) {
                        if ($item->item_type === 'pack' && $item->pack_id) {
                            $this->stockService->restoreStockForReturn($item->pack_id, $item->quantity, $ref);
                        } elseif ($item->product_id && $item->product) {
                            $this->productRepo->adjustStock(
                                $item->product,
                                $item->quantity * $item->units_per_item,
                                'return_sale',
                                $ref
                            );
                        }
                    }
                }
            });
        } catch (Throwable $e) {
            $this->logError($e, 'Erreur lors de la création du retour de vente', ['sale_id' => $sale->id, 'request' => $request->except('_token')]);
            return back()->withInput()->with('error', 'Une erreur est survenue lors de l\'enregistrement du retour.');
        }

        return redirect()->route('sale-returns.index')->with('success', 'Retour enregistré avec succès.');
    }
}
