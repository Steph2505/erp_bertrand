<?php

namespace App\Http\Controllers\Accounts;

use App\Http\Controllers\Controller;
use App\Models\Expense;
use App\Models\Payment;
use App\Models\PaymentAccount;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\Sale;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PaymentAccountController extends Controller
{
    public function index(Request $request): View
    {
        $accounts = PaymentAccount::withCount('payments')->latest()->get();
        return view('pages.accounts.index', compact('accounts'));
    }

    public function show(PaymentAccount $paymentAccount, Request $request): View
    {
        $dateFrom = $request->input('date_from');
        $dateTo   = $request->input('date_to');

        // Paiements liés à des ventes (encaissements)
        $inflows = Payment::with('payable')
            ->where('payment_account_id', $paymentAccount->id)
            ->where('payable_type', Sale::class)
            ->when($dateFrom, fn($q) => $q->whereDate('payment_date', '>=', $dateFrom))
            ->when($dateTo,   fn($q) => $q->whereDate('payment_date', '<=', $dateTo))
            ->get()
            ->map(fn($p) => (object) [
                'date'      => $p->payment_date,
                'reference' => $p->reference,
                'label'     => 'Vente ' . ($p->payable?->reference ?? '—'),
                'method'    => $p->payment_method,
                'debit'     => 0,
                'credit'    => (float) $p->amount,
                'link'      => $p->payable ? route('sales.show', $p->payable_id) : null,
            ]);

        // Paiements liés à des achats (décaissements)
        $outflows = Payment::with('payable')
            ->where('payment_account_id', $paymentAccount->id)
            ->where('payable_type', Purchase::class)
            ->when($dateFrom, fn($q) => $q->whereDate('payment_date', '>=', $dateFrom))
            ->when($dateTo,   fn($q) => $q->whereDate('payment_date', '<=', $dateTo))
            ->get()
            ->map(fn($p) => (object) [
                'date'      => $p->payment_date,
                'reference' => $p->reference,
                'label'     => 'Achat ' . ($p->payable?->reference ?? '—'),
                'method'    => $p->payment_method,
                'debit'     => (float) $p->amount,
                'credit'    => 0,
                'link'      => $p->payable ? route('purchases.show', $p->payable_id) : null,
            ]);

        // Dépenses rattachées à ce compte
        $expenses = Expense::where('payment_account_id', $paymentAccount->id)
            ->when($dateFrom, fn($q) => $q->whereDate('expense_date', '>=', $dateFrom))
            ->when($dateTo,   fn($q) => $q->whereDate('expense_date', '<=', $dateTo))
            ->get()
            ->map(fn($e) => (object) [
                'date'      => $e->expense_date,
                'reference' => $e->reference,
                'label'     => 'Dépense' . ($e->description ? ' — ' . $e->description : ''),
                'method'    => '—',
                'debit'     => (float) $e->amount,
                'credit'    => 0,
                'link'      => null,
            ]);

        $journal = $inflows->concat($outflows)->concat($expenses)
            ->sortBy('date')
            ->values();

        $totalCredits = $journal->sum('credit');
        $totalDebits  = $journal->sum('debit');

        return view('pages.accounts.show', compact(
            'paymentAccount', 'journal', 'totalCredits', 'totalDebits', 'dateFrom', 'dateTo'
        ));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name'            => 'required|string|max:191',
            'type'            => 'required|in:cash,bank,mobile_money',
            'opening_balance' => 'nullable|numeric|min:0',
            'account_number'  => 'nullable|string|max:100',
        ]);

        if ($request->boolean('is_default')) {
            PaymentAccount::where('is_default', true)->update(['is_default' => false]);
        }

        PaymentAccount::create(array_merge($data, [
            'current_balance' => $data['opening_balance'] ?? 0,
            'is_active'       => true,
            'is_default'      => $request->boolean('is_default'),
        ]));

        return back()->with('success', 'Compte créé avec succès.');
    }

    public function update(Request $request, PaymentAccount $paymentAccount): RedirectResponse
    {
        $data = $request->validate([
            'name'           => 'required|string|max:191',
            'type'           => 'required|in:cash,bank,mobile_money',
            'account_number' => 'nullable|string|max:100',
            'is_active'      => 'boolean',
        ]);

        if ($request->boolean('is_default')) {
            PaymentAccount::where('id', '!=', $paymentAccount->id)->update(['is_default' => false]);
            $data['is_default'] = true;
        }

        $paymentAccount->update($data);
        return back()->with('success', 'Compte mis à jour.');
    }

    public function destroy(PaymentAccount $paymentAccount): RedirectResponse
    {
        $paymentAccount->delete();
        return back()->with('success', 'Compte supprimé.');
    }

    // ──────────────────────────────────────────────────────────────────────────
    // Bilan
    // ──────────────────────────────────────────────────────────────────────────
    public function balanceSheet(): View
    {
        // Actif — Trésorerie
        $accounts        = PaymentAccount::where('is_active', true)->get();
        $totalTresorerie = $accounts->sum('current_balance');

        // Actif — Valeur du stock (coût d'achat)
        $stockValue = Product::whereNull('deleted_at')
            ->selectRaw('SUM(stock_quantity * buying_price) as total')
            ->value('total') ?? 0;

        // Actif — Créances clients (ventes confirmées non intégralement payées)
        $creancesClients = Sale::where('status', 'confirmed')
            ->whereIn('payment_status', ['pending', 'partial'])
            ->selectRaw('SUM(total - amount_paid) as total')
            ->value('total') ?? 0;

        $totalActif = $totalTresorerie + $stockValue + $creancesClients;

        // Passif — Dettes fournisseurs
        $dettesFournisseurs = Purchase::whereIn('payment_status', ['pending', 'partial'])
            ->selectRaw('SUM(total - amount_paid) as total')
            ->value('total') ?? 0;

        $totalPassif   = $dettesFournisseurs;
        $situationNette = $totalActif - $totalPassif;

        return view('pages.accounts.balance-sheet', compact(
            'accounts', 'totalTresorerie', 'stockValue', 'creancesClients',
            'totalActif', 'dettesFournisseurs', 'totalPassif', 'situationNette'
        ));
    }

    // ──────────────────────────────────────────────────────────────────────────
    // Balance de vérification
    // ──────────────────────────────────────────────────────────────────────────
    public function trialBalance(Request $request): View
    {
        $year  = (int) $request->input('year', now()->year);
        $start = "$year-01-01";
        $end   = "$year-12-31";

        $accounts = PaymentAccount::all()->map(function (PaymentAccount $acc) use ($start, $end) {
            // Encaissements = paiements de ventes enregistrés sur ce compte
            $encaissements = Payment::where('payment_account_id', $acc->id)
                ->where('payable_type', Sale::class)
                ->whereBetween('payment_date', [$start, $end])
                ->sum('amount');

            // Décaissements = paiements d'achats + dépenses sur ce compte
            $decAchats = Payment::where('payment_account_id', $acc->id)
                ->where('payable_type', Purchase::class)
                ->whereBetween('payment_date', [$start, $end])
                ->sum('amount');

            $decDepenses = Expense::where('payment_account_id', $acc->id)
                ->whereBetween('expense_date', [$start, $end])
                ->sum('amount');

            $decaissements = $decAchats + $decDepenses;

            return (object) [
                'name'           => $acc->name,
                'type'           => $acc->type,
                'opening'        => (float) $acc->opening_balance,
                'encaissements'  => (float) $encaissements,
                'decaissements'  => (float) $decaissements,
                'solde_calcule'  => (float) $acc->opening_balance + $encaissements - $decaissements,
                'solde_reel'     => (float) $acc->current_balance,
            ];
        });

        $years = range(now()->year, max(now()->year - 4, 2024));

        return view('pages.accounts.trial-balance', compact('accounts', 'year', 'years'));
    }

    // ──────────────────────────────────────────────────────────────────────────
    // Flux de trésorerie
    // ──────────────────────────────────────────────────────────────────────────
    public function cashFlow(Request $request): View
    {
        $year  = (int) $request->input('year', now()->year);
        $years = range(now()->year, max(now()->year - 4, 2024));

        $months = [];
        for ($m = 1; $m <= 12; $m++) {
            $start = sprintf('%d-%02d-01', $year, $m);
            $end   = sprintf('%d-%02d-%02d', $year, $m, cal_days_in_month(CAL_GREGORIAN, $m, $year));

            $encaissements = Payment::where('payable_type', Sale::class)
                ->whereBetween('payment_date', [$start, $end])
                ->sum('amount');

            $decAchats = Payment::where('payable_type', Purchase::class)
                ->whereBetween('payment_date', [$start, $end])
                ->sum('amount');

            $decDepenses = Expense::whereBetween('expense_date', [$start, $end])->sum('amount');

            $decaissements = $decAchats + $decDepenses;

            $months[] = (object) [
                'label'         => \Carbon\Carbon::create($year, $m)->isoFormat('MMM'),
                'encaissements' => (float) $encaissements,
                'dec_achats'    => (float) $decAchats,
                'dec_depenses'  => (float) $decDepenses,
                'decaissements' => (float) $decaissements,
                'net'           => (float) $encaissements - $decaissements,
            ];
        }

        $totals = (object) [
            'encaissements' => collect($months)->sum('encaissements'),
            'dec_achats'    => collect($months)->sum('dec_achats'),
            'dec_depenses'  => collect($months)->sum('dec_depenses'),
            'decaissements' => collect($months)->sum('decaissements'),
            'net'           => collect($months)->sum('net'),
        ];

        return view('pages.accounts.cash-flow', compact('months', 'totals', 'year', 'years'));
    }
}
