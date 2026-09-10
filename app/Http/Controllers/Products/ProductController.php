<?php

namespace App\Http\Controllers\Products;

use App\Http\Controllers\Controller;
use App\Http\Requests\ProductRequest;
use App\Models\Category;
use App\Models\Product;
use App\Models\Setting;
use App\Models\Unit;
use App\Repositories\ProductRepository;
use App\Helpers\FormatHelper;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Throwable;

class ProductController extends Controller
{
    public function __construct(private readonly ProductRepository $repo) {}

    public function index(Request $request): View|RedirectResponse
    {
        try {
            $categories = Category::where('is_active', true)->orderBy('name')->get();
            return view('pages.products.index', compact('categories'));
        } catch (Throwable $e) {
            $this->logError($e, 'Erreur lors du chargement de la page des produits');
            return redirect()->route('dashboard')->with('error', 'Une erreur est survenue lors du chargement de la page.');
        }
    }

    public function apiIndex(Request $request): JsonResponse
    {
        try {
            $filters = array_filter([
                'search'      => $request->get('search'),
                'category_id' => $request->get('category_id'),
                'is_active'   => $request->get('is_active') !== null && $request->get('is_active') !== ''
                                    ? $request->get('is_active') : null,
                'low_stock'   => $request->boolean('low_stock') ? true : null,
            ], fn($v) => $v !== null);

            $paginator = $this->repo->paginate($filters, 10, $request->integer('page', 1));

            $data = $paginator->getCollection()->map(fn($p) => [
                'id'           => $p->id,
                'name'         => $p->display_name,
                'barcode'      => $p->barcode,
                'image_url'    => $p->getFirstMediaUrl('images'),
                'category'     => $p->category?->name ?? '—',
                'buying_price' => FormatHelper::money($p->buying_price),
                'selling_price'=> FormatHelper::money($p->selling_price),
                'can_be_packed'=> $p->can_be_packed,
                'pack_quantity'=> $p->pack_quantity ?? 1,
                'is_active'    => $p->is_active,
                'show_url'     => route('products.show', $p->id),
                'edit_url'     => route('products.edit', $p->id),
                'delete_url'   => route('products.destroy', $p->id),
            ]);

            return response()->json([
                'data'         => $data,
                'total'        => $paginator->total(),
                'per_page'     => $paginator->perPage(),
                'current_page' => $paginator->currentPage(),
                'last_page'    => $paginator->lastPage(),
                'from'         => $paginator->firstItem() ?? 0,
                'to'           => $paginator->lastItem() ?? 0,
            ]);
        } catch (Throwable $e) {
            $this->logError($e, 'Erreur lors du chargement des produits', ['filters' => $request->all()]);
            return response()->json(['message' => 'Impossible de charger les produits.'], 500);
        }
    }

    public function create(): View|RedirectResponse
    {
        try {
            $categories = Category::where('is_active', true)->orderBy('name')->get();
            $units      = Unit::orderBy('name')->get();

            return view('pages.products.create', compact('categories', 'units'));
        } catch (Throwable $e) {
            $this->logError($e, 'Erreur lors du chargement du formulaire de produit');
            return redirect()->route('products.index')->with('error', 'Une erreur est survenue lors du chargement de la page.');
        }
    }

    public function store(ProductRequest $request): RedirectResponse|JsonResponse
    {
        $data = $request->validated();
        $canBePacked            = $request->boolean('can_be_packed');
        $data['can_be_packed']  = $canBePacked;
        $data['pack_quantity']  = $canBePacked ? max(1, (int) ($request->pack_quantity ?? 1)) : 1;
        $data['pack_price']     = $canBePacked && $request->filled('pack_price') ? (float) $request->pack_price : null;
        $data['is_active']      = $request->boolean('is_active', true);
        $data['has_variations'] = $request->boolean('has_variations');

        try {
            $product = $this->repo->create($data);

            $defaultWarehouseId = (int) Setting::get('default_warehouse_id');
            if ($defaultWarehouseId) {
                $this->repo->syncWarehouseStocks($product, [
                    $defaultWarehouseId => max(0, (int) ($data['stock_quantity'] ?? 0)),
                ]);
            }

            if ($request->hasFile('images')) {
                foreach ($request->file('images') as $image) {
                    $product->addMedia($image)->toMediaCollection('images');
                }
            }
        } catch (Throwable $e) {
            $this->logError($e, 'Erreur lors de la création du produit', ['data' => $data]);

            if ($request->wantsJson()) {
                return response()->json(['message' => 'Une erreur est survenue lors de la création du produit.'], 500);
            }
            return back()->withInput()->with('error', 'Une erreur est survenue lors de la création du produit.');
        }

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'product' => [
                    'id'            => $product->id,
                    'name'          => $product->display_name,
                    'buying_price'  => (float) $product->buying_price,
                    'selling_price' => (float) $product->selling_price,
                    'stock_quantity'=> (int) $product->stock_quantity,
                    'can_be_packed' => (bool) $product->can_be_packed,
                    'pack_quantity' => (int) ($product->pack_quantity ?? 1),
                    'stocks'        => [],
                ],
            ]);
        }

        return redirect()->route('products.index')->with('success', 'Produit créé avec succès.');
    }

    public function show(Product $product): View|RedirectResponse
    {
        try {
            $product->load(['category', 'unit', 'brand', 'variations', 'stockMovements' => fn($q) => $q->latest()->limit(20)]);
            return view('pages.products.show', compact('product'));
        } catch (Throwable $e) {
            $this->logError($e, 'Erreur lors du chargement du produit', ['product_id' => $product->id]);
            return redirect()->route('products.index')->with('error', 'Une erreur est survenue lors du chargement du produit.');
        }
    }

    public function edit(Product $product): View|RedirectResponse
    {
        try {
            $categories = Category::where('is_active', true)->orderBy('name')->get();
            $units      = Unit::orderBy('name')->get();

            return view('pages.products.edit', compact('product', 'categories', 'units'));
        } catch (Throwable $e) {
            $this->logError($e, 'Erreur lors du chargement du formulaire de modification de produit', ['product_id' => $product->id]);
            return redirect()->route('products.show', $product)->with('error', 'Une erreur est survenue lors du chargement de la page.');
        }
    }

    public function update(ProductRequest $request, Product $product): RedirectResponse
    {
        $data = $request->validated();
        $canBePacked            = $request->boolean('can_be_packed');
        $data['can_be_packed']  = $canBePacked;
        $data['pack_quantity']  = $canBePacked ? max(1, (int) ($request->pack_quantity ?? 1)) : 1;
        $data['pack_price']     = $canBePacked && $request->filled('pack_price') ? (float) $request->pack_price : null;
        $data['is_active']      = $request->boolean('is_active', true);
        $data['has_variations'] = $request->boolean('has_variations');

        try {
            $this->repo->update($product, $data);

            if ($request->hasFile('images')) {
                foreach ($request->file('images') as $image) {
                    $product->addMedia($image)->toMediaCollection('images');
                }
            }
        } catch (Throwable $e) {
            $this->logError($e, 'Erreur lors de la mise à jour du produit', ['product_id' => $product->id, 'data' => $data]);
            return back()->withInput()->with('error', 'Une erreur est survenue lors de la mise à jour du produit.');
        }

        return redirect()->route('products.index')->with('success', 'Produit mis à jour.');
    }

    public function destroy(Product $product): RedirectResponse
    {
        try {
            $this->repo->delete($product);
        } catch (Throwable $e) {
            $this->logError($e, 'Erreur lors de la suppression du produit', ['product_id' => $product->id]);
            return back()->with('error', 'Une erreur est survenue lors de la suppression du produit.');
        }

        return redirect()->route('products.index')->with('success', 'Produit supprimé.');
    }

    public function toggleActive(Product $product): RedirectResponse
    {
        try {
            $product->update(['is_active' => !$product->is_active]);
        } catch (Throwable $e) {
            $this->logError($e, 'Erreur lors du changement de statut du produit', ['product_id' => $product->id]);
            return back()->with('error', 'Une erreur est survenue.');
        }

        return back()->with('success', 'Statut mis à jour.');
    }

    public function search(Request $request)
    {
        try {
            $query = $request->get('q', '');
            $products = $this->repo->searchPackable($query);

            return response()->json($products->map(fn($p) => [
                'id'            => $p->id,
                'name'          => $p->display_name,
                'buying_price'  => $p->buying_price,
                'selling_price' => $p->selling_price,
                'stock'         => $p->stock_quantity,
                'pack_quantity' => $p->pack_quantity ?? 1,
                'type'          => 'product',
            ]));
        } catch (Throwable $e) {
            $this->logError($e, 'Erreur lors de la recherche de produits', ['query' => $request->get('q')]);
            return response()->json(['message' => 'Une erreur est survenue lors de la recherche.'], 500);
        }
    }
}
