<?php

namespace App\Http\Controllers\Expenses;

use App\Http\Controllers\Controller;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\PaymentAccount;
use App\Models\Purchase;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Throwable;

class ExpenseController extends Controller
{
    public function index(Request $request): View|RedirectResponse
    {
        try {
        // ── Dépenses
        $expenseRows = Expense::with(['category', 'paymentAccount'])
            ->when($request->search, fn($q, $s) => $q->where('description', 'like', "%$s%")->orWhere('reference', 'like', "%$s%"))
            ->when($request->category_id, fn($q, $id) => $q->where('expense_category_id', $id))
            ->when($request->date_from, fn($q, $d) => $q->whereDate('expense_date', '>=', $d))
            ->when($request->date_to, fn($q, $d) => $q->whereDate('expense_date', '<=', $d))
            ->get()
            ->map(fn($e) => (object)[
                'type'        => 'expense',
                'id'          => $e->id,
                'reference'   => $e->reference,
                'category'    => $e->category?->name,
                'description' => $e->description,
                'account'     => $e->paymentAccount?->name,
                'date'        => $e->expense_date,
                'amount'      => (float) $e->amount,
                'model'       => $e,
            ]);

        // ── Achats fournisseurs payés (exclus si filtre catégorie actif)
        $purchaseRows = collect();
        if (! $request->category_id) {
            $purchaseRows = Purchase::with('supplier')
                ->where('payment_status', 'paid')
                ->when($request->search, fn($q, $s) => $q->where('reference', 'like', "%$s%")
                    ->orWhereHas('supplier', fn($q2) => $q2->where('name', 'like', "%$s%")))
                ->when($request->date_from, fn($q, $d) => $q->whereDate('purchase_date', '>=', $d))
                ->when($request->date_to, fn($q, $d) => $q->whereDate('purchase_date', '<=', $d))
                ->get()
                ->map(fn($p) => (object)[
                    'type'        => 'purchase',
                    'id'          => $p->id,
                    'reference'   => $p->reference,
                    'category'    => null,
                    'description' => $p->supplier?->name ?? '—',
                    'account'     => null,
                    'date'        => $p->purchase_date,
                    'amount'      => (float) $p->amount_paid,
                    'model'       => $p,
                ]);
        }

        $merged      = $expenseRows->concat($purchaseRows)->sortByDesc('date')->values();
        $totalPeriod = $merged->sum('amount');

        $perPage  = 20;
        $page     = (int) $request->input('page', 1);
        $expenses = new LengthAwarePaginator(
            $merged->forPage($page, $perPage),
            $merged->count(),
            $perPage,
            $page,
            ['path' => $request->url(), 'query' => $request->query()]
        );

        $categories = ExpenseCategory::orderBy('name')->get();
        $accounts   = PaymentAccount::where('is_active', true)->orderBy('name')->get();

        return view('pages.expenses.index', compact('expenses', 'categories', 'accounts', 'totalPeriod'));
        } catch (Throwable $e) {
            $this->logError($e, 'Erreur lors du chargement de la page des dépenses', ['filters' => $request->all()]);
            return redirect()->route('dashboard')->with('error', 'Une erreur est survenue lors du chargement de la page.');
        }
    }

    public function categoriesIndex(): View|RedirectResponse
    {
        try {
            $categories = ExpenseCategory::withCount('expenses')->orderBy('name')->paginate(30);
            return view('pages.expenses.categories', compact('categories'));
        } catch (Throwable $e) {
            $this->logError($e, 'Erreur lors du chargement des catégories de dépenses');
            return redirect()->route('expenses.index')->with('error', 'Une erreur est survenue lors du chargement de la page.');
        }
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'expense_category_id' => 'required|exists:expense_categories,id',
            'payment_account_id'  => 'required|exists:payment_accounts,id',
            'expense_date'        => 'required|date',
            'amount'              => 'required|numeric|min:0.01',
            'description'         => 'nullable|string|max:500',
        ]);

        try {
            DB::transaction(function () use ($data) {
                $reference = 'EXP-' . date('Ymd') . '-' . str_pad(Expense::withTrashed()->count() + 1, 4, '0', STR_PAD_LEFT);
                Expense::create(array_merge($data, ['reference' => $reference, 'created_by' => auth()->id()]));

                if ($data['payment_account_id'] ?? null) {
                    PaymentAccount::find($data['payment_account_id'])?->decrement('current_balance', $data['amount']);
                }
            });
        } catch (Throwable $e) {
            $this->logError($e, 'Erreur lors de la création de la dépense', ['data' => $data]);
            return back()->withInput()->with('error', 'Une erreur est survenue lors de l\'enregistrement de la dépense.');
        }

        return back()->with('success', 'Dépense enregistrée.');
    }

    public function update(Request $request, Expense $expense): RedirectResponse
    {
        $data = $request->validate([
            'expense_category_id' => 'required|exists:expense_categories,id',
            'payment_account_id'  => 'required|exists:payment_accounts,id',
            'expense_date'        => 'required|date',
            'amount'              => 'required|numeric|min:0.01',
            'description'         => 'nullable|string|max:500',
        ]);

        try {
            DB::transaction(function () use ($data, $expense) {
                // Inverser l'effet de l'ancienne dépense sur l'ancien compte
                if ($expense->payment_account_id) {
                    PaymentAccount::find($expense->payment_account_id)?->increment('current_balance', $expense->amount);
                }

                $expense->update($data);

                // Appliquer l'effet de la nouvelle dépense sur le nouveau compte
                if ($data['payment_account_id'] ?? null) {
                    PaymentAccount::find($data['payment_account_id'])?->decrement('current_balance', $data['amount']);
                }
            });
        } catch (Throwable $e) {
            $this->logError($e, 'Erreur lors de la mise à jour de la dépense', ['expense_id' => $expense->id, 'data' => $data]);
            return back()->withInput()->with('error', 'Une erreur est survenue lors de la mise à jour de la dépense.');
        }

        return back()->with('success', 'Dépense mise à jour.');
    }

    public function destroy(Expense $expense): RedirectResponse
    {
        try {
            DB::transaction(function () use ($expense) {
                if ($expense->payment_account_id) {
                    PaymentAccount::find($expense->payment_account_id)?->increment('current_balance', $expense->amount);
                }

                $expense->delete();
            });
        } catch (Throwable $e) {
            $this->logError($e, 'Erreur lors de la suppression de la dépense', ['expense_id' => $expense->id]);
            return back()->with('error', 'Une erreur est survenue lors de la suppression de la dépense.');
        }

        return back()->with('success', 'Dépense supprimée.');
    }

    public function storeCategory(Request $request): RedirectResponse
    {
        $request->validate(['name' => 'required|string|max:191|unique:expense_categories,name']);

        try {
            ExpenseCategory::create(['name' => $request->name]);
        } catch (Throwable $e) {
            $this->logError($e, 'Erreur lors de la création de la catégorie de dépense', ['name' => $request->name]);
            return back()->withInput()->with('error', 'Une erreur est survenue lors de la création de la catégorie.');
        }

        return back()->with('success', 'Catégorie créée.');
    }

    public function updateCategory(Request $request, ExpenseCategory $category): RedirectResponse
    {
        $request->validate(['name' => 'required|string|max:191|unique:expense_categories,name,' . $category->id]);

        try {
            $category->update(['name' => $request->name]);
        } catch (Throwable $e) {
            $this->logError($e, 'Erreur lors de la mise à jour de la catégorie de dépense', ['category_id' => $category->id]);
            return back()->withInput()->with('error', 'Une erreur est survenue lors de la mise à jour de la catégorie.');
        }

        return back()->with('success', 'Catégorie mise à jour.');
    }

    public function destroyCategory(ExpenseCategory $category): RedirectResponse
    {
        if ($category->expenses()->exists()) {
            return back()->withErrors(['category' => 'Impossible : cette catégorie contient des dépenses.']);
        }

        try {
            $category->delete();
        } catch (Throwable $e) {
            $this->logError($e, 'Erreur lors de la suppression de la catégorie de dépense', ['category_id' => $category->id]);
            return back()->with('error', 'Une erreur est survenue lors de la suppression de la catégorie.');
        }

        return back()->with('success', 'Catégorie supprimée.');
    }
}
