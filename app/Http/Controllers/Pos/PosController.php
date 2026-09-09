<?php

namespace App\Http\Controllers\Pos;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\PosSession;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\Warehouse;
use App\Repositories\ProductRepository;
use App\Services\PackStockService;
use App\Services\PaymentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class PosController extends Controller
{
    public function __construct(
        private readonly ProductRepository $productRepo,
        private readonly PackStockService  $stockService,
        private readonly PaymentService    $paymentService,
    ) {}

    public function index(): View
    {
        $customers          = Customer::where('is_active', true)->orderBy('name')->get(['id', 'name', 'phone']);
        $warehouses         = Warehouse::where('is_active', true)->orderBy('name')->get(['id', 'name']);
        $caisses            = \App\Models\Caisse::where('is_active', true)->orderBy('name')->get(['id', 'name']);
        $defaultWarehouseId = (int) \App\Models\Setting::get('default_warehouse_id');
        $activeSession      = PosSession::where('user_id', auth()->id())
            ->whereNull('closed_at')
            ->with(['warehouse', 'caisse'])
            ->latest()
            ->first();

        return view('pages.pos.index', compact('customers', 'warehouses', 'caisses', 'defaultWarehouseId', 'activeSession'));
    }

    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'items'        => 'required|array|min:1',
            'payment_mode' => 'required|string',
            'amount_paid'  => 'required|numeric|min:0',
        ]);

        $activeSession = PosSession::where('user_id', auth()->id())
            ->whereNull('closed_at')->latest()->first();

        if (!$activeSession || !$activeSession->caisse_id) {
            return response()->json(['success' => false, 'message' => 'Aucune caisse ouverte.'], 403);
        }

        $sale = DB::transaction(function () use ($request, $activeSession) {
            $reference = 'POS-' . date('Ymd') . '-' . str_pad(Sale::where('is_pos', true)->count() + 1, 4, '0', STR_PAD_LEFT);

            $sale = Sale::create([
                'reference'      => $reference,
                'customer_id'    => $request->customer_id ?: null,
                'warehouse_id'   => $activeSession->warehouse_id ?: ($request->warehouse_id ?: null),
                'pos_session_id' => $activeSession->id,
                'sale_date'      => now()->toDateString(),
                'status'         => 'completed',
                'payment_status' => (float) $request->amount_paid >= (float) $request->total ? 'paid' : 'partial',
                'is_pos'         => true,
                'note'           => $request->note,
                'created_by'     => auth()->id(),
            ]);

            $subtotal = 0;
            foreach ($request->items as $item) {
                $isPack = ($item['item_type'] ?? 'product') === 'pack';
                $lineTotal = $item['quantity'] * $item['unit_price'];
                $subtotal += $lineTotal;

                SaleItem::create([
                    'sale_id'        => $sale->id,
                    'product_id'     => $isPack ? null : $item['product_id'],
                    'pack_id'        => $isPack ? $item['pack_id'] : null,
                    'item_type'      => $item['item_type'] ?? 'product',
                    'item_name'      => $item['item_name'],
                    'quantity'       => $item['quantity'],
                    'units_per_item' => $isPack ? ($item['units_per_item'] ?? 1) : 1,
                    'unit_price'     => $item['unit_price'],
                    'discount'       => 0,
                    'tax_rate'       => 0,
                    'subtotal'       => $lineTotal,
                ]);

                $warehouseId = $activeSession->warehouse_id ?? null;

                if ($isPack) {
                    $this->stockService->deductStockForSale($item['pack_id'], $item['quantity'], $reference, $warehouseId);
                } else {
                    $product = \App\Models\Product::find($item['product_id']);
                    if ($product) {
                        $unitsPerItem = (int) ($item['units_per_item'] ?? 1);
                        $this->productRepo->adjustStock($product, -($item['quantity'] * $unitsPerItem), 'sale', $reference, null, null, $warehouseId);
                    }
                }
            }

            $amountPaid = (float) $request->amount_paid;

            $sale->update([
                'subtotal'    => $subtotal,
                'total'       => $subtotal,
                'amount_paid' => $amountPaid,
            ]);

            $activeSession->increment('total_sales', $subtotal);

            if ($amountPaid > 0) {
                $this->paymentService->recordInflow(
                    $sale,
                    min($amountPaid, $subtotal),
                    $request->payment_mode ?? 'cash',
                    $request->input('payment_account_id') ? (int) $request->input('payment_account_id') : null
                );
            }

            return $sale;
        });

        return response()->json([
            'success'   => true,
            'reference' => $sale->reference,
            'sale_id'   => $sale->id,
            'change'    => max(0, (float) $request->amount_paid - $sale->total),
        ]);
    }

    public function pay(Request $request, Sale $sale): JsonResponse
    {
        abort_unless($sale->is_pos, 404);

        $due = round((float) $sale->total - (float) $sale->amount_paid, 2);

        $request->validate([
            'amount'             => 'required|numeric|min:0.01|max:' . $due,
            'payment_mode'       => 'required|string',
            'payment_account_id' => 'nullable|exists:payment_accounts,id',
        ]);

        DB::transaction(function () use ($request, $sale) {
            $newPaid = round((float) $sale->amount_paid + (float) $request->amount, 2);
            $status  = $newPaid >= (float) $sale->total ? 'paid' : 'partial';

            $sale->update([
                'amount_paid'    => $newPaid,
                'payment_status' => $status,
            ]);

            $this->paymentService->recordInflow(
                $sale,
                (float) $request->amount,
                $request->payment_mode,
                $request->input('payment_account_id') ? (int) $request->input('payment_account_id') : null
            );
        });

        $sale->refresh();

        return response()->json([
            'success'        => true,
            'payment_status' => $sale->payment_status,
            'amount_due'     => max(0, (float) $sale->total - (float) $sale->amount_paid),
        ]);
    }

    public function showByReference(string $reference): View
    {
        $sale = Sale::where('reference', $reference)->where('is_pos', true)->firstOrFail();
        $sale->load(['customer', 'items', 'posSession.caisse', 'warehouse', 'createdBy']);
        return view('pages.pos.ticket', compact('sale'));
    }

    public function receipt(Sale $sale): View
    {
        $sale->load(['customer', 'items', 'warehouse', 'posSession.caisse', 'createdBy']);
        return view('print.pos-receipt', compact('sale'));
    }

    public function list(Request $request): View
    {
        return view('pages.pos.list');
    }

    public function apiList(Request $request): JsonResponse
    {
        $paginator = Sale::with(['customer'])
            ->where('is_pos', true)
            ->when($request->date, fn($q, $d) => $q->whereDate('sale_date', $d))
            ->latest()
            ->paginate(10, ['*'], 'page', $request->integer('page', 1));

        return response()->json([
            'data' => $paginator->getCollection()->map(fn($s) => [
                'id'             => $s->id,
                'reference'      => $s->reference,
                'customer'       => $s->customer?->name ?? 'Client comptoir',
                'time'           => $s->created_at->format('H:i'),
                'total'          => \App\Helpers\FormatHelper::money($s->total),
                'amount_paid'    => \App\Helpers\FormatHelper::money($s->amount_paid),
                'amount_due'     => (float) $s->amount_due,
                'payment_status' => $s->payment_status,
                'pay_badge'      => \App\Helpers\FormatHelper::statusBadge($s->payment_status),
                'receipt_url'    => route('pos.receipt', $s->id),
                'pay_url'        => '/pos/' . $s->id . '/pay',
            ]),
            'total'        => $paginator->total(),
            'per_page'     => $paginator->perPage(),
            'current_page' => $paginator->currentPage(),
            'last_page'    => $paginator->lastPage(),
            'from'         => $paginator->firstItem() ?? 0,
            'to'           => $paginator->lastItem() ?? 0,
        ]);
    }
}
