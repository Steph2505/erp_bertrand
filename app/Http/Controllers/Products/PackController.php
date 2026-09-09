<?php

namespace App\Http\Controllers\Products;

use App\Http\Controllers\Controller;
use App\Http\Requests\PackRequest;
use App\Models\Pack;
use App\Models\PriceGroup;
use App\Models\Product;
use App\Repositories\PackRepository;
use App\Services\PackStockService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PackController extends Controller
{
    public function __construct(
        private readonly PackRepository  $repo,
        private readonly PackStockService $stockService,
    ) {}

    public function index(Request $request): View
    {
        $packs       = $this->repo->paginate($request->only(['search', 'is_active']));
        $stockService = $this->stockService;

        return view('pages.products.packs.index', compact('packs', 'stockService'));
    }

    public function create(): View
    {
        $packableProducts = Product::where('can_be_packed', true)->where('is_active', true)->orderBy('name')->get();
        $priceGroups      = PriceGroup::orderBy('name')->get();

        return view('pages.products.packs.create', compact('packableProducts', 'priceGroups'));
    }

    public function store(PackRequest $request): RedirectResponse
    {
        $data = $request->safe()->except(['items', 'prices', 'image']);
        $data['is_active'] = $request->boolean('is_active', true);

        $pack = $this->repo->create($data, $request->input('items', []), $request->input('prices', []));

        if ($request->hasFile('image')) {
            $pack->addMedia($request->file('image'))->toMediaCollection('images');
        }

        return redirect()->route('packs.index')->with('success', "Pack « {$pack->name} » créé avec succès.");
    }

    public function show(Pack $pack): View
    {
        $pack->load(['items.product.unit', 'prices.priceGroup']);
        $availableCount = $this->stockService->availablePackCount($pack->id);

        return view('pages.products.packs.show', compact('pack', 'availableCount'));
    }

    public function edit(Pack $pack): View
    {
        $pack->load(['items.product', 'prices.priceGroup']);
        $packableProducts = Product::where('can_be_packed', true)->where('is_active', true)->orderBy('name')->get();
        $priceGroups      = PriceGroup::orderBy('name')->get();

        return view('pages.products.packs.edit', compact('pack', 'packableProducts', 'priceGroups'));
    }

    public function update(PackRequest $request, Pack $pack): RedirectResponse
    {
        $data = $request->safe()->except(['items', 'prices', 'image']);
        $data['is_active'] = $request->boolean('is_active', true);

        $this->repo->update($pack, $data, $request->input('items', []), $request->input('prices', []));

        if ($request->hasFile('image')) {
            $pack->clearMediaCollection('images');
            $pack->addMedia($request->file('image'))->toMediaCollection('images');
        }

        return redirect()->route('packs.index')->with('success', "Pack « {$pack->name} » mis à jour.");
    }

    public function destroy(Pack $pack): RedirectResponse
    {
        $this->repo->delete($pack);
        return redirect()->route('packs.index')->with('success', 'Pack supprimé.');
    }

    public function toggleActive(Pack $pack): RedirectResponse
    {
        $this->repo->toggleActive($pack);
        return back()->with('success', 'Statut du pack mis à jour.');
    }

    // ─── API AJAX ────────────────────────────────────────────────────────────

    public function searchPackableProducts(Request $request): JsonResponse
    {
        $search   = $request->get('q', '');
        $products = Product::where('can_be_packed', true)
            ->where('is_active', true)
            ->where('name', 'like', "%$search%")
            ->limit(10)
            ->get(['id', 'name', 'variation', 'buying_price', 'selling_price', 'stock_quantity', 'unit_id']);

        return response()->json($products->map(fn($p) => [
            'id'           => $p->id,
            'name'         => $p->display_name,
            'buying_price' => $p->buying_price,
            'stock'        => $p->stock_quantity,
        ]));
    }

    public function checkStock(Request $request, Pack $pack): JsonResponse
    {
        $quantity = (int) $request->get('quantity', 1);
        $hasStock = $this->stockService->hasEnoughStock($pack->id, $quantity);
        $available = $this->stockService->availablePackCount($pack->id);

        return response()->json([
            'has_enough' => $hasStock,
            'available'  => $available,
            'needed'     => $quantity,
        ]);
    }

    public function searchForSale(Request $request): JsonResponse
    {
        $query = $request->get('q', '');
        $results = $this->repo->searchForSale($query);
        return response()->json($results);
    }
}
