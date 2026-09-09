<?php

use App\Http\Controllers\Accounts\PaymentAccountController;
use App\Http\Controllers\Auth\ForgotPasswordController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\ResetPasswordController;
use App\Http\Controllers\Contacts\CustomerController;
use App\Http\Controllers\Contacts\SupplierController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\Expenses\ExpenseController;
use App\Http\Controllers\Pos\PosController;
use App\Http\Controllers\Pos\PosSessionController;
use App\Http\Controllers\Products\CategoryController;
use App\Http\Controllers\Products\PackController;
use App\Http\Controllers\Products\UnitController;
use App\Http\Controllers\Sales\QuotationController;
use App\Http\Controllers\Sales\SaleReturnController;
use App\Http\Controllers\Products\ProductController;
use App\Http\Controllers\Pos\CaisseController;
use App\Http\Controllers\Purchases\PurchaseController;
use App\Http\Controllers\Purchases\PurchaseReturnController;
use App\Http\Controllers\Sales\SaleController;
use App\Http\Controllers\Settings\SettingController;
use App\Http\Controllers\Stock\StockAdjustmentController;
use App\Http\Controllers\Stock\StockStatusController;
use App\Http\Controllers\Stock\StockTransferController;
use App\Http\Controllers\Users\UserController;
use App\Http\Controllers\Reports\ReportController;
use Illuminate\Support\Facades\Route;

// ─── Auth ─────────────────────────────────────────────────────────────────────
Route::middleware('guest')->group(function () {
    Route::get('/login',                  [LoginController::class, 'showLoginForm'])->name('login');
    Route::post('/login',                 [LoginController::class, 'login']);
    Route::get('/forgot-password',        [ForgotPasswordController::class, 'showForm'])->name('password.request');
    Route::post('/forgot-password',       [ForgotPasswordController::class, 'sendLink'])->name('password.email');
    Route::get('/reset-password/{token}', [ResetPasswordController::class, 'showForm'])->name('password.reset');
    Route::post('/reset-password',        [ResetPasswordController::class, 'reset'])->name('password.update');
});

Route::post('/logout', [LoginController::class, 'logout'])->name('logout');

// ─── Application (auth requise) ───────────────────────────────────────────────
Route::middleware('auth')->group(function () {

    Route::get('/',              [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/dashboard/stats', [DashboardController::class, 'apiStats'])->name('dashboard.stats');

    // ── Produits ──────────────────────────────────────────────────────────────
    Route::prefix('products')->name('products.')->middleware('permission:manage products')->group(function () {
        Route::get('/',                          [ProductController::class, 'index'])->name('index');
        Route::get('/create',                    [ProductController::class, 'create'])->name('create');
        Route::post('/',                         [ProductController::class, 'store'])->name('store');
        Route::get('/api/search',                [ProductController::class, 'search'])->name('search');
        Route::get('/api/list',                  [ProductController::class, 'apiIndex'])->name('api.list');
        Route::get('/{product}',                 [ProductController::class, 'show'])->name('show');
        Route::get('/{product}/edit',            [ProductController::class, 'edit'])->name('edit');
        Route::put('/{product}',                 [ProductController::class, 'update'])->name('update');
        Route::delete('/{product}',              [ProductController::class, 'destroy'])->name('destroy');
        Route::patch('/{product}/toggle-active', [ProductController::class, 'toggleActive'])->name('toggle-active');
    });

    // ── Packs ─────────────────────────────────────────────────────────────────
    Route::prefix('packs')->name('packs.')->middleware('permission:manage products')->group(function () {
        Route::get('/search-products',           [PackController::class, 'searchPackableProducts'])->name('search-products');
        Route::get('/search-for-sale',           [PackController::class, 'searchForSale'])->name('search-for-sale');
        Route::get('/',                          [PackController::class, 'index'])->name('index');
        Route::get('/create',                    [PackController::class, 'create'])->name('create');
        Route::post('/',                         [PackController::class, 'store'])->name('store');
        Route::get('/{pack}',                    [PackController::class, 'show'])->name('show');
        Route::get('/{pack}/edit',               [PackController::class, 'edit'])->name('edit');
        Route::put('/{pack}',                    [PackController::class, 'update'])->name('update');
        Route::delete('/{pack}',                 [PackController::class, 'destroy'])->name('destroy');
        Route::patch('/{pack}/toggle-active',    [PackController::class, 'toggleActive'])->name('toggle-active');
        Route::get('/{pack}/check-stock',        [PackController::class, 'checkStock'])->name('check-stock');
    });

    // ── Unités ───────────────────────────────────────────────────────────────
    Route::prefix('units')->name('units.')->middleware('permission:manage products')->group(function () {
        Route::get('/',           [UnitController::class, 'index'])->name('index');
        Route::post('/',          [UnitController::class, 'store'])->name('store');
        Route::put('/{unit}',     [UnitController::class, 'update'])->name('update');
        Route::delete('/{unit}',  [UnitController::class, 'destroy'])->name('destroy');
    });

    // ── Catégories produits ───────────────────────────────────────────────────
    Route::prefix('categories')->name('categories.')->middleware('permission:manage products')->group(function () {
        Route::get('/',               [CategoryController::class, 'index'])->name('index');
        Route::post('/',              [CategoryController::class, 'store'])->name('store');
        Route::put('/{category}',     [CategoryController::class, 'update'])->name('update');
        Route::delete('/{category}',  [CategoryController::class, 'destroy'])->name('destroy');
    });

    Route::name('brands.index')->get('/brands', fn() => redirect()->route('products.index'));
    Route::name('price-groups.index')->get('/price-groups', fn() => redirect()->route('products.index'));

    // ── Achats ────────────────────────────────────────────────────────────────
    Route::prefix('purchases')->name('purchases.')->middleware('permission:manage purchases')->group(function () {
        Route::get('/',                      [PurchaseController::class, 'index'])->name('index');
        Route::get('/api/list',              [PurchaseController::class, 'apiIndex'])->name('api.list');
        Route::get('/create',               [PurchaseController::class, 'create'])->name('create');
        Route::post('/',                    [PurchaseController::class, 'store'])->name('store');
        Route::get('/{purchase}/edit',      [PurchaseController::class, 'edit'])->name('edit');
        Route::put('/{purchase}',           [PurchaseController::class, 'update'])->name('update');
        Route::get('/{purchase}',           [PurchaseController::class, 'show'])->name('show');
        Route::patch('/{purchase}/confirm', [PurchaseController::class, 'confirm'])->name('confirm');
        Route::post('/{purchase}/pay',      [PurchaseController::class, 'pay'])->name('pay');
        Route::delete('/{purchase}',        [PurchaseController::class, 'destroy'])->name('destroy');
    });
    Route::prefix('purchase-returns')->name('purchase-returns.')->middleware('permission:manage purchases')->group(function () {
        Route::get('/',       [PurchaseReturnController::class, 'index'])->name('index');
        Route::get('/create', [PurchaseReturnController::class, 'create'])->name('create');
        Route::post('/',      [PurchaseReturnController::class, 'store'])->name('store');
    });

    // ── Ventes ────────────────────────────────────────────────────────────────
    // L'endpoint de recherche est aussi utilisé par le POS
    Route::get('/sales/api/search', [SaleController::class, 'searchItems'])->name('sales.search-items');
    Route::prefix('sales')->name('sales.')->middleware('permission:manage sales')->group(function () {
        Route::get('/',                  [SaleController::class, 'index'])->name('index');
        Route::get('/api/list',          [SaleController::class, 'apiIndex'])->name('api.list');
        Route::get('/create',            [SaleController::class, 'create'])->name('create');
        Route::post('/',                 [SaleController::class, 'store'])->name('store');
        Route::get('/{sale}/edit',       [SaleController::class, 'edit'])->name('edit');
        Route::put('/{sale}',            [SaleController::class, 'update'])->name('update');
        Route::get('/{sale}',            [SaleController::class, 'show'])->name('show');
        Route::patch('/{sale}/confirm',  [SaleController::class, 'confirm'])->name('confirm');
        Route::post('/{sale}/pay',       [SaleController::class, 'pay'])->name('pay');
        Route::delete('/{sale}',         [SaleController::class, 'destroy'])->name('destroy');
    });
    // ── Retours ventes ────────────────────────────────────────────────────────
    Route::prefix('sale-returns')->name('sale-returns.')->middleware('permission:manage sales')->group(function () {
        Route::get('/',        [SaleReturnController::class, 'index'])->name('index');
        Route::get('/create',  [SaleReturnController::class, 'create'])->name('create');
        Route::post('/',       [SaleReturnController::class, 'store'])->name('store');
    });

    // ── Devis ─────────────────────────────────────────────────────────────────
    Route::prefix('quotations')->name('quotations.')->middleware('permission:manage sales')->group(function () {
        Route::get('/',                         [QuotationController::class, 'index'])->name('index');
        Route::get('/api/list',                 [QuotationController::class, 'apiIndex'])->name('api.list');
        Route::get('/create',                   [QuotationController::class, 'create'])->name('create');
        Route::post('/',                        [QuotationController::class, 'store'])->name('store');
        Route::get('/{quotation}',              [QuotationController::class, 'show'])->name('show');
        Route::patch('/{quotation}/status',     [QuotationController::class, 'updateStatus'])->name('status');
        Route::post('/{quotation}/convert',     [QuotationController::class, 'convertToSale'])->name('convert');
        Route::delete('/{quotation}',           [QuotationController::class, 'destroy'])->name('destroy');
    });

    // ── POS ───────────────────────────────────────────────────────────────────
    Route::prefix('pos')->name('pos.')->middleware('permission:manage pos')->group(function () {
        Route::get('/',                    [PosController::class, 'index'])->name('index');
        Route::post('/store',              [PosController::class, 'store'])->name('store');
        Route::get('/list',                [PosController::class, 'list'])->name('list');
        Route::get('/api/list',            [PosController::class, 'apiList'])->name('api.list');
        Route::get('/ref/{reference}',     [PosController::class, 'showByReference'])->name('show-ref');
        Route::get('/{sale}/receipt',      [PosController::class, 'receipt'])->name('receipt');
        Route::post('/{sale}/pay',         [PosController::class, 'pay'])->name('pay');
        // Sessions caisse
        Route::get('/sessions',            [PosSessionController::class, 'index'])->name('sessions.index');
        Route::get('/sessions/api/list',   [PosSessionController::class, 'apiIndex'])->name('sessions.api.list');
        Route::post('/sessions/open',      [PosSessionController::class, 'open'])->name('sessions.open');
        Route::post('/sessions/{session}/close', [PosSessionController::class, 'close'])->name('sessions.close');
        Route::get('/sessions/{session}',  [PosSessionController::class, 'show'])->name('sessions.show');
        // Gestion des caisses
        Route::get('/caisses',                    [CaisseController::class, 'index'])->name('caisses.index');
        Route::get('/caisses/search',             [CaisseController::class, 'search'])->name('caisses.search');
        Route::post('/caisses',                   [CaisseController::class, 'store'])->name('caisses.store');
        Route::get('/caisses/{caisse}',           [CaisseController::class, 'show'])->name('caisses.show');
        Route::put('/caisses/{caisse}',           [CaisseController::class, 'update'])->name('caisses.update');
        Route::patch('/caisses/{caisse}/toggle',  [CaisseController::class, 'toggleActive'])->name('caisses.toggle');
        Route::delete('/caisses/{caisse}',        [CaisseController::class, 'destroy'])->name('caisses.destroy');
    });

    // ── Stock ─────────────────────────────────────────────────────────────────
    Route::get('/stock-status',          [StockStatusController::class, 'index'])->middleware('permission:manage stock')->name('stock-status.index');
    Route::get('/stock-status/api/list', [StockStatusController::class, 'apiIndex'])->middleware('permission:manage stock')->name('stock-status.api.list');
    Route::prefix('stock-adjustments')->name('stock-adjustments.')->middleware('permission:manage stock')->group(function () {
        Route::get('/',           [StockAdjustmentController::class, 'index'])->name('index');
        Route::get('/api/list',   [StockAdjustmentController::class, 'apiIndex'])->name('api.list');
        Route::get('/create', [StockAdjustmentController::class, 'index'])->name('create');
        Route::post('/',      [StockAdjustmentController::class, 'store'])->name('store');
    });
    Route::prefix('stock-transfers')->name('stock-transfers.')->middleware('permission:manage stock')->group(function () {
        Route::get('/',           [StockTransferController::class, 'index'])->name('index');
        Route::get('/api/list',   [StockTransferController::class, 'apiIndex'])->name('api.list');
        Route::get('/create', [StockTransferController::class, 'index'])->name('create');
        Route::post('/',      [StockTransferController::class, 'store'])->name('store');
    });

    // ── Dépenses ─────────────────────────────────────────────────────────────
    Route::prefix('expenses')->name('expenses.')->middleware('permission:manage expenses')->group(function () {
        Route::get('/',                                [ExpenseController::class, 'index'])->name('index');
        Route::get('/create',                          fn() => redirect()->route('expenses.index', ['new' => 1]))->name('create');
        Route::post('/',                               [ExpenseController::class, 'store'])->name('store');
        Route::put('/{expense}',                       [ExpenseController::class, 'update'])->name('update');
        Route::delete('/{expense}',                    [ExpenseController::class, 'destroy'])->name('destroy');
        Route::post('/categories',                     [ExpenseController::class, 'storeCategory'])->name('categories.store');
        Route::put('/categories/{category}',           [ExpenseController::class, 'updateCategory'])->name('categories.update');
        Route::delete('/categories/{category}',        [ExpenseController::class, 'destroyCategory'])->name('categories.destroy');
    });
    Route::name('expense-categories.index')->get('/expense-categories', [ExpenseController::class, 'categoriesIndex']);

    // ── Comptes ──────────────────────────────────────────────────────────────
    Route::prefix('payment-accounts')->name('payment-accounts.')->middleware('permission:manage accounts')->group(function () {
        Route::get('/',                    [PaymentAccountController::class, 'index'])->name('index');
        Route::post('/',                   [PaymentAccountController::class, 'store'])->name('store');
        Route::get('/{paymentAccount}',    [PaymentAccountController::class, 'show'])->name('show');
        Route::put('/{paymentAccount}',    [PaymentAccountController::class, 'update'])->name('update');
        Route::delete('/{paymentAccount}', [PaymentAccountController::class, 'destroy'])->name('destroy');
    });
    Route::name('accounts.balance-sheet')->get('/accounts/balance-sheet', [PaymentAccountController::class, 'balanceSheet']);
    Route::name('accounts.trial-balance')->get('/accounts/trial-balance',  [PaymentAccountController::class, 'trialBalance']);
    Route::name('accounts.cash-flow')->get('/accounts/cash-flow',          [PaymentAccountController::class, 'cashFlow']);

    // ── Contacts ─────────────────────────────────────────────────────────────
    Route::prefix('customers')->name('customers.')->middleware('permission:manage customers')->group(function () {
        Route::get('/',               [CustomerController::class, 'index'])->name('index');
        Route::post('/',              [CustomerController::class, 'store'])->name('store');
        Route::get('/{customer}',     [CustomerController::class, 'show'])->name('show');
        Route::put('/{customer}',     [CustomerController::class, 'update'])->name('update');
        Route::delete('/{customer}',  [CustomerController::class, 'destroy'])->name('destroy');
    });
    Route::name('customer-groups.index')->get('/customer-groups', fn() => redirect()->route('customers.index'));
    Route::name('contacts.import')->get('/contacts/import',       fn() => redirect()->route('customers.index'));

    Route::prefix('suppliers')->name('suppliers.')->middleware('permission:manage suppliers')->group(function () {
        Route::get('/',               [SupplierController::class, 'index'])->name('index');
        Route::post('/',              [SupplierController::class, 'store'])->name('store');
        Route::get('/{supplier}',     [SupplierController::class, 'show'])->name('show');
        Route::put('/{supplier}',     [SupplierController::class, 'update'])->name('update');
        Route::delete('/{supplier}',  [SupplierController::class, 'destroy'])->name('destroy');
    });

    // ── Rapports ─────────────────────────────────────────────────────────────
    Route::prefix('reports')->name('reports.')->middleware('permission:view reports')->controller(ReportController::class)->group(function () {
        Route::get('/profit-loss',        'profitLoss')->name('profit-loss');
        Route::get('/purchase-sale',      'purchaseSale')->name('purchase-sale');
        Route::get('/tax',                'tax')->name('tax');
        Route::get('/contacts',           'contacts')->name('contacts');
        Route::get('/customer-groups',    fn() => redirect()->route('customers.index'))->name('customer-groups');
        Route::get('/stock',              'stock')->name('stock');
        Route::get('/expiry',             'expiry')->name('expiry');
        Route::get('/stock-adjustment',   'stockAdjustment')->name('stock-adjustment');
        Route::get('/trending',           'trending')->name('trending');
        Route::get('/items',              'items')->name('items');
        Route::get('/product-purchase',   'productPurchase')->name('product-purchase');
        Route::get('/product-sale',       'productSale')->name('product-sale');
        Route::get('/purchase-payments',  'purchasePayments')->name('purchase-payments');
        Route::get('/sale-payments',      'salePayments')->name('sale-payments');
        Route::get('/expenses',           'expenses')->name('expenses');
        Route::get('/pos',                'pos')->name('pos');
        Route::get('/agents',             'agents')->name('agents');
        Route::get('/activity',           'activity')->name('activity');
    });

    // ── Utilisateurs ─────────────────────────────────────────────────────────
    Route::prefix('users')->name('users.')->middleware('permission:manage users')->group(function () {
        Route::get('/',          [UserController::class, 'index'])->name('index');
        Route::post('/',         [UserController::class, 'store'])->name('store');
        Route::put('/{user}',    [UserController::class, 'update'])->name('update');
        Route::delete('/{user}', [UserController::class, 'destroy'])->name('destroy');
    });
    Route::get('/profile',   [UserController::class, 'profile'])->name('users.profile');
    Route::put('/profile',   [UserController::class, 'updateProfile'])->name('users.profile.update');
    Route::name('roles.index')->get('/roles', fn() => redirect()->route('users.index'));

    // ── Paramètres ───────────────────────────────────────────────────────────
    Route::prefix('settings')->name('settings.')->middleware('permission:manage settings')->controller(SettingController::class)->group(function () {
        Route::get('/company',                  'company')->name('company');
        Route::post('/company',                 'saveCompany')->name('company.save');
        Route::get('/warehouses',                         'warehouses')->name('warehouses');
        Route::post('/warehouses',                        'storeWarehouse')->name('warehouses.store');
        Route::put('/warehouses/{warehouse}',             'updateWarehouse')->name('warehouses.update');
        Route::delete('/warehouses/{warehouse}',          'destroyWarehouse')->name('warehouses.destroy');
        Route::post('/warehouses/{warehouse}/set-default','setDefaultWarehouse')->name('warehouses.set-default');
        Route::get('/invoices',                 'invoices')->name('invoices');
        Route::post('/invoices',                'saveInvoices')->name('invoices.save');
        Route::get('/tax-rates',                'taxRates')->name('tax-rates');
        Route::post('/tax-rates',               'saveTaxRates')->name('tax-rates.save');
    });

    // ── Impression ───────────────────────────────────────────────────────────
    Route::get('/print/sale/{sale}', function (\App\Models\Sale $sale) {
        return view('print.sale', ['sale' => $sale->load(['customer', 'warehouse', 'items', 'createdBy'])]);
    })->name('print.sale');

    Route::get('/print/purchase/{purchase}', function (\App\Models\Purchase $purchase) {
        return view('print.purchase', ['purchase' => $purchase->load(['supplier', 'warehouse', 'items', 'createdBy'])]);
    })->name('print.purchase');
});
