<?php

namespace App\Http\Controllers\Pos;

use App\Http\Controllers\Controller;
use App\Models\PaymentAccount;
use App\Models\PosSession;
use App\Models\Sale;
use App\Models\SaleReturn;
use App\Models\SaleReturnItem;
use App\Repositories\ProductRepository;
use App\Services\PackStockService;
use App\Services\PaymentService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Throwable;

class SaleReturnController extends Controller
{
    public function __construct(
        private readonly ProductRepository $productRepo,
        private readonly PackStockService $stockService,
        private readonly PaymentService $paymentService,
    ) {}

    public function index(): View|RedirectResponse
    {
        try {
            $returns = SaleReturn::with(['sale', 'createdBy', 'items', 'posSession'])
                ->when(
                    ! auth()->user()->hasRole('Super Admin'),
                    fn ($query) => $query->whereHas('sale', fn ($saleQuery) => $saleQuery->where('created_by', auth()->id()))
                )
                ->orderByDesc('return_date')
                ->paginate(20);

            return view('pages.pos.returns', compact('returns'));
        } catch (Throwable $e) {
            $this->logError($e, 'Erreur lors du chargement de la page des retours de vente');

            return redirect()->route('dashboard')->with('error', 'Une erreur est survenue lors du chargement de la page.');
        }
    }

    public function create(Request $request): View|RedirectResponse
    {
        $sale = null;
        if ($request->sale_id) {
            $sale = $this->visibleSalesQuery()
                ->with(['customer', 'items.returnItems'])
                ->whereKey($request->sale_id)
                ->firstOrFail();
        }

        try {
            $sales = $this->visibleSalesQuery()
                ->with('customer')
                ->where('status', 'completed')
                ->orderByDesc('sale_date')
                ->get(['id', 'reference', 'customer_id', 'total', 'sale_date'])
                ->map(fn ($saleOption) => [
                    'id' => $saleOption->id,
                    'reference' => $saleOption->reference,
                    'customer' => $saleOption->customer?->name ?? 'Comptoir',
                    'total' => (float) $saleOption->total,
                ]);

            $saleLines = $sale?->items->map(fn ($item) => [
                'id' => $item->id,
                'item_name' => $item->item_name,
                'item_type' => $item->item_type,
                'quantity' => (int) $item->quantity,
                'units_per_item' => (int) $item->units_per_item,
                'subtotal' => (float) $item->subtotal,
                'returned_quantity' => (int) $item->returnItems->sum('quantity'),
                'returned_total' => (float) $item->returnItems->sum('total'),
            ])->values() ?? collect();
            $hasLegacyReturns = $sale?->returns()->whereDoesntHave('items')->exists() ?? false;
            $paymentAccounts = PaymentAccount::where('is_active', true)->orderBy('name')->get(['id', 'name', 'type']);

            return view('pages.pos.return-create', compact('sale', 'sales', 'saleLines', 'hasLegacyReturns', 'paymentAccounts'));
        } catch (Throwable $e) {
            $this->logError($e, 'Erreur lors du chargement du formulaire de retour de vente', ['sale_id' => $request->sale_id]);

            return redirect()->route('pos.returns.index')->with('error', 'Une erreur est survenue lors du chargement de la page.');
        }
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'sale_id' => 'required|exists:sales,id',
            'return_date' => 'required|date',
            'items' => 'required|array|min:1',
            'items.*' => 'required|array',
            'items.*.quantity' => 'required|integer|min:0',
            'items.*.stock_condition' => 'required|in:resellable,damaged',
            'reason' => 'nullable|string|max:500',
            'payment_method' => 'required|in:cash,mobile_money,bank_transfer,check,card',
            'payment_account_id' => ['nullable', Rule::exists('payment_accounts', 'id')->where('is_active', true)],
        ]);

        if ($request->filled('payment_account_id')) {
            $account = PaymentAccount::findOrFail($request->input('payment_account_id'));
            $expectedAccountType = match ($request->input('payment_method')) {
                'cash' => 'cash',
                'mobile_money' => 'mobile_money',
                'bank_transfer', 'check', 'card' => 'bank',
            };

            if ($account->type !== $expectedAccountType) {
                throw ValidationException::withMessages([
                    'payment_account_id' => 'Le compte de sortie doit correspondre au mode de remboursement choisi.',
                ]);
            }
        }

        $this->visibleSalesQuery()->whereKey($request->sale_id)->firstOrFail();

        try {
            DB::transaction(function () use ($request) {
                $sale = $this->visibleSalesQuery()
                    ->whereKey($request->sale_id)
                    ->lockForUpdate()
                    ->firstOrFail();
                if ($sale->status !== 'completed') {
                    throw ValidationException::withMessages([
                        'sale_id' => 'Seules les ventes terminées peuvent faire l’objet d’un retour.',
                    ]);
                }
                if ($sale->returns()->whereDoesntHave('items')->exists()) {
                    throw ValidationException::withMessages([
                        'sale_id' => 'Cette vente a un ancien retour sans détail par article. Impossible d’enregistrer un nouveau retour automatiquement.',
                    ]);
                }

                $saleItems = $sale->items()->with('product')->lockForUpdate()->get()->keyBy('id');
                $requestedItems = collect($request->input('items'));
                if ($requestedItems->keys()->diff($saleItems->keys())->isNotEmpty()) {
                    throw ValidationException::withMessages([
                        'items' => 'Un ou plusieurs articles ne correspondent pas à cette vente.',
                    ]);
                }

                $previousReturns = SaleReturnItem::whereIn('sale_item_id', $saleItems->keys())
                    ->get(['sale_item_id', 'quantity', 'total'])
                    ->groupBy('sale_item_id')
                    ->map(fn ($returns) => [
                        'quantity' => (int) $returns->sum('quantity'),
                        'total' => (float) $returns->sum('total'),
                    ]);

                $lines = [];
                $total = 0;
                foreach ($requestedItems as $saleItemId => $input) {
                    $quantity = (int) $input['quantity'];
                    if ($quantity === 0) {
                        continue;
                    }

                    $saleItem = $saleItems->get($saleItemId);
                    $previous = $previousReturns->get($saleItemId, ['quantity' => 0, 'total' => 0]);
                    $remaining = (int) $saleItem->quantity - $previous['quantity'];
                    if ($quantity > $remaining) {
                        throw ValidationException::withMessages([
                            "items.{$saleItemId}.quantity" => "La quantité retournée dépasse la quantité restante pour « {$saleItem->item_name} ».",
                        ]);
                    }

                    $cumulativeQuantity = $previous['quantity'] + $quantity;
                    $cumulativeTotal = round((float) $saleItem->subtotal * $cumulativeQuantity / (int) $saleItem->quantity, 2);
                    $lineTotal = round($cumulativeTotal - $previous['total'], 2);
                    $total += $lineTotal;
                    $lines[] = [
                        'sale_item' => $saleItem,
                        'quantity' => $quantity,
                        'total' => $lineTotal,
                        'stock_condition' => $input['stock_condition'],
                    ];
                }

                if ($lines === []) {
                    throw ValidationException::withMessages([
                        'items' => 'Sélectionnez au moins un article à retourner.',
                    ]);
                }

                $ref = 'RET-'.date('Ymd').'-'.str_pad(
                    SaleReturn::whereDate('created_at', today())->count() + 1, 4, '0', STR_PAD_LEFT
                );
                $activeSession = $request->payment_method === 'cash'
                    ? PosSession::where('user_id', auth()->id())
                        ->whereNull('closed_at')
                        ->lockForUpdate()
                        ->latest('opened_at')
                        ->first()
                    : null;
                if ($request->payment_method === 'cash'
                    && ! $activeSession
                    && ! auth()->user()->hasAnyRole(['Admin', 'Super Admin'])
                    && ! auth()->user()->can('close pos sessions')) {
                    throw ValidationException::withMessages([
                        'payment_method' => 'Un remboursement en espèces hors session doit être validé par un responsable.',
                    ]);
                }

                $saleReturn = SaleReturn::create([
                    'reference' => $ref,
                    'sale_id' => $sale->id,
                    'pos_session_id' => $activeSession?->id,
                    'return_date' => $request->return_date,
                    'total' => $total,
                    'reason' => $request->reason,
                    'created_by' => auth()->id(),
                ]);

                foreach ($lines as $line) {
                    $saleItem = $line['sale_item'];
                    SaleReturnItem::create([
                        'sale_return_id' => $saleReturn->id,
                        'sale_item_id' => $saleItem->id,
                        'item_name' => $saleItem->item_name,
                        'quantity' => $line['quantity'],
                        'total' => $line['total'],
                        'cost_total' => $saleItem->unit_cost === null
                            ? null
                            : $line['quantity'] * ($saleItem->item_type === 'pack' ? 1 : $saleItem->units_per_item) * $saleItem->unit_cost,
                        'stock_condition' => $line['stock_condition'],
                    ]);

                    if ($line['stock_condition'] !== 'resellable') {
                        continue;
                    }

                    if ($saleItem->item_type === 'pack' && $saleItem->pack_id) {
                        $this->stockService->restoreStockForReturn(
                            $saleItem->pack_id,
                            $line['quantity'],
                            $ref,
                            $sale->warehouse_id
                        );
                    } elseif ($saleItem->product_id && $saleItem->product) {
                        $this->productRepo->adjustStock(
                            $saleItem->product,
                            $line['quantity'] * $saleItem->units_per_item,
                            'return_sale',
                            $ref,
                            null,
                            "Retour de {$line['quantity']} × {$saleItem->item_name}",
                            $sale->warehouse_id
                        );
                    }
                }

                if ($total > 0) {
                    $payment = $this->paymentService->recordOutflow(
                        $saleReturn,
                        $total,
                        $request->payment_method,
                        $request->input('payment_account_id') ? (int) $request->input('payment_account_id') : null,
                        $request->return_date
                    );
                    $payment->update(['note' => "Remboursement du retour {$ref}"]);
                }
            });
        } catch (ValidationException $e) {
            throw $e;
        } catch (Throwable $e) {
            $this->logError($e, 'Erreur lors de la création du retour de vente', [
                'sale_id' => $request->sale_id,
                'request' => $request->except('_token'),
            ]);

            return back()->withInput()->with('error', 'Une erreur est survenue lors de l\'enregistrement du retour.');
        }

        return redirect()->route('pos.returns.index')->with('success', 'Retour enregistré avec succès.');
    }

    private function visibleSalesQuery(): Builder
    {
        $user = auth()->user();
        $query = Sale::query();

        if (! $user->hasRole('Super Admin')) {
            $query->where('created_by', $user->id);
        }

        return $query;
    }
}
