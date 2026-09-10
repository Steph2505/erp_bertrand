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
use Throwable;

class PackController extends Controller
{
    public function __construct(
        private readonly PackRepository  $repo,
        private readonly PackStockService $stockService,
    ) {}

    public function index(Request $request): View|RedirectResponse
    {
        try {
            $packs       = $this->repo->paginate($request->only(['search', 'is_active']));
            $stockService = $this->stockService;

            return view('pages.products.packs.index', compact('packs', 'stockService'));
        } catch (Throwable $e) {
            $this->logError($e, 'Erreur lors du chargement de la page des packs');
            return redirect()->route('dashboard')->with('error', 'Une erreur est survenue lors du chargement de la page.');
        }
    }

    public function create(): View|RedirectResponse
    {
        try {
            $packableProducts = Product::where('can_be_packed', true)->where('is_active', true)->orderBy('name')->get();
            $priceGroups      = PriceGroup::orderBy('name')->get();

            return view('pages.products.packs.create', compact('packableProducts', 'priceGroups'));
        } catch (Throwable $e) {
            $this->logError($e, 'Erreur lors du chargement du formulaire de pack');
            return redirect()->route('packs.index')->with('error', 'Une erreur est survenue lors du chargement de la page.');
        }
    }

    public function store(PackRequest $request): RedirectResponse
    {
        $data = $request->safe()->except(['items', 'prices', 'image']);
        $data['is_active'] = $request->boolean('is_active', true);

        try {
            $pack = $this->repo->create($data, $request->input('items', []), $request->input('prices', []));

            if ($request->hasFile('image')) {
                $pack->addMedia($request->file('image'))->toMediaCollection('images');
            }
        } catch (Throwable $e) {
            $this->logError($e, 'Erreur lors de la création du pack', ['data' => $data]);
            return back()->withInput()->with('error', 'Une erreur est survenue lors de la création du pack.');
        }

        return redirect()->route('packs.index')->with('success', "Pack « {$pack->name} » créé avec succès.");
    }

    public function show(Pack $pack): View|RedirectResponse
    {
        try {
            $pack->load(['items.product.unit', 'prices.priceGroup']);
            $availableCount = $this->stockService->availablePackCount($pack->id);

            return view('pages.products.packs.show', compact('pack', 'availableCount'));
        } catch (Throwable $e) {
            $this->logError($e, 'Erreur lors du chargement du pack', ['pack_id' => $pack->id]);
            return redirect()->route('packs.index')->with('error', 'Une erreur est survenue lors du chargement du pack.');
        }
    }

    public function edit(Pack $pack): View|RedirectResponse
    {
        try {
            $pack->load(['items.product', 'prices.priceGroup']);
            $packableProducts = Product::where('can_be_packed', true)->where('is_active', true)->orderBy('name')->get();
            $priceGroups      = PriceGroup::orderBy('name')->get();

            return view('pages.products.packs.edit', compact('pack', 'packableProducts', 'priceGroups'));
        } catch (Throwable $e) {
            $this->logError($e, 'Erreur lors du chargement du formulaire de modification de pack', ['pack_id' => $pack->id]);
            return redirect()->route('packs.show', $pack)->with('error', 'Une erreur est survenue lors du chargement de la page.');
        }
    }

    public function update(PackRequest $request, Pack $pack): RedirectResponse
    {
        $data = $request->safe()->except(['items', 'prices', 'image']);
        $data['is_active'] = $request->boolean('is_active', true);

        try {
            $this->repo->update($pack, $data, $request->input('items', []), $request->input('prices', []));

            if ($request->hasFile('image')) {
                $pack->clearMediaCollection('images');
                $pack->addMedia($request->file('image'))->toMediaCollection('images');
            }
        } catch (Throwable $e) {
            $this->logError($e, 'Erreur lors de la mise à jour du pack', ['pack_id' => $pack->id, 'data' => $data]);
            return back()->withInput()->with('error', 'Une erreur est survenue lors de la mise à jour du pack.');
        }

        return redirect()->route('packs.index')->with('success', "Pack « {$pack->name} » mis à jour.");
    }

    public function destroy(Pack $pack): RedirectResponse
    {
        try {
            $this->repo->delete($pack);
        } catch (Throwable $e) {
            $this->logError($e, 'Erreur lors de la suppression du pack', ['pack_id' => $pack->id]);
            return back()->with('error', 'Une erreur est survenue lors de la suppression du pack.');
        }

        return redirect()->route('packs.index')->with('success', 'Pack supprimé.');
    }

    public function toggleActive(Pack $pack): RedirectResponse
    {
        try {
            $this->repo->toggleActive($pack);
        } catch (Throwable $e) {
            $this->logError($e, 'Erreur lors du changement de statut du pack', ['pack_id' => $pack->id]);
            return back()->with('error', 'Une erreur est survenue.');
        }

        return back()->with('success', 'Statut du pack mis à jour.');
    }

    // ─── API AJAX ────────────────────────────────────────────────────────────

    public function searchPackableProducts(Request $request): JsonResponse
    {
        try {
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
        } catch (Throwable $e) {
            $this->logError($e, 'Erreur lors de la recherche de produits pour pack', ['query' => $request->get('q')]);
            return response()->json(['message' => 'Une erreur est survenue lors de la recherche.'], 500);
        }
    }

    public function checkStock(Request $request, Pack $pack): JsonResponse
    {
        try {
            $quantity = (int) $request->get('quantity', 1);
            $hasStock = $this->stockService->hasEnoughStock($pack->id, $quantity);
            $available = $this->stockService->availablePackCount($pack->id);

            return response()->json([
                'has_enough' => $hasStock,
                'available'  => $available,
                'needed'     => $quantity,
            ]);
        } catch (Throwable $e) {
            $this->logError($e, 'Erreur lors de la vérification du stock du pack', ['pack_id' => $pack->id]);
            return response()->json(['message' => 'Une erreur est survenue.'], 500);
        }
    }

    public function searchForSale(Request $request): JsonResponse
    {
        try {
            $query = $request->get('q', '');
            $results = $this->repo->searchForSale($query);
            return response()->json($results);
        } catch (Throwable $e) {
            $this->logError($e, 'Erreur lors de la recherche de packs pour la vente', ['query' => $request->get('q')]);
            return response()->json(['message' => 'Une erreur est survenue lors de la recherche.'], 500);
        }
    }
}
