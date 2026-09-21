<?php

namespace App\Http\Controllers\Accounts;

use App\Helpers\FormatHelper;
use App\Http\Controllers\Controller;
use App\Models\AccountTransfer;
use App\Models\Expense;
use App\Models\Payment;
use App\Models\PaymentAccount;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\Sale;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Throwable;

class PaymentAccountController extends Controller
{
    public function index(Request $request): View|RedirectResponse
    {
        try {
            $accounts = PaymentAccount::withCount('payments')->latest()->get();
            return view('pages.accounts.index', compact('accounts'));
        } catch (Throwable $e) {
            $this->logError($e, 'Erreur lors du chargement de la page des comptes de paiement');
            return redirect()->route('dashboard')->with('error', 'Une erreur est survenue lors du chargement de la page.');
        }
    }

    public function show(PaymentAccount $paymentAccount, Request $request): View|RedirectResponse
    {
        try {
            return view('pages.accounts.show', compact('paymentAccount'));
        } catch (Throwable $e) {
            $this->logError($e, 'Erreur lors du chargement du relevé du compte', ['account_id' => $paymentAccount->id]);
            return redirect()->route('payment-accounts.index')->with('error', 'Une erreur est survenue lors du chargement du relevé.');
        }
    }

    public function apiShow(PaymentAccount $paymentAccount, Request $request): JsonResponse
    {
        try {
            $dateFrom = $request->input('date_from');
            $dateTo   = $request->input('date_to');

            $inflows = DB::table('payments')
                ->join('sales', 'sales.id', '=', 'payments.payable_id')
                ->where('payments.payment_account_id', $paymentAccount->id)
                ->where('payments.payable_type', Sale::class)
                ->when($dateFrom, fn($q) => $q->whereDate('payments.payment_date', '>=', $dateFrom))
                ->when($dateTo,   fn($q) => $q->whereDate('payments.payment_date', '<=', $dateTo))
                ->selectRaw("payments.payment_date as date, payments.reference as reference, 'sale' as type, payments.payable_id as payable_id, sales.reference as ref_label, payments.payment_method as method, 0 as debit, payments.amount as credit");

            $outflows = DB::table('payments')
                ->join('purchases', 'purchases.id', '=', 'payments.payable_id')
                ->where('payments.payment_account_id', $paymentAccount->id)
                ->where('payments.payable_type', Purchase::class)
                ->when($dateFrom, fn($q) => $q->whereDate('payments.payment_date', '>=', $dateFrom))
                ->when($dateTo,   fn($q) => $q->whereDate('payments.payment_date', '<=', $dateTo))
                ->selectRaw("payments.payment_date as date, payments.reference as reference, 'purchase' as type, payments.payable_id as payable_id, purchases.reference as ref_label, payments.payment_method as method, payments.amount as debit, 0 as credit");

            $expenseRows = DB::table('expenses')
                ->where('payment_account_id', $paymentAccount->id)
                ->when($dateFrom, fn($q) => $q->whereDate('expense_date', '>=', $dateFrom))
                ->when($dateTo,   fn($q) => $q->whereDate('expense_date', '<=', $dateTo))
                ->selectRaw("expense_date as date, reference as reference, 'expense' as type, id as payable_id, description as ref_label, '—' as method, amount as debit, 0 as credit");

            $transfersOut = DB::table('account_transfers')
                ->join('payment_accounts', 'payment_accounts.id', '=', 'account_transfers.to_account_id')
                ->where('account_transfers.from_account_id', $paymentAccount->id)
                ->when($dateFrom, fn($q) => $q->whereDate('account_transfers.transfer_date', '>=', $dateFrom))
                ->when($dateTo,   fn($q) => $q->whereDate('account_transfers.transfer_date', '<=', $dateTo))
                ->selectRaw("account_transfers.transfer_date as date, account_transfers.reference as reference, 'transfer_out' as type, account_transfers.id as payable_id, payment_accounts.name as ref_label, '—' as method, account_transfers.amount as debit, 0 as credit");

            $transfersIn = DB::table('account_transfers')
                ->join('payment_accounts', 'payment_accounts.id', '=', 'account_transfers.from_account_id')
                ->where('account_transfers.to_account_id', $paymentAccount->id)
                ->when($dateFrom, fn($q) => $q->whereDate('account_transfers.transfer_date', '>=', $dateFrom))
                ->when($dateTo,   fn($q) => $q->whereDate('account_transfers.transfer_date', '<=', $dateTo))
                ->selectRaw("account_transfers.transfer_date as date, account_transfers.reference as reference, 'transfer_in' as type, account_transfers.id as payable_id, payment_accounts.name as ref_label, '—' as method, 0 as debit, account_transfers.amount as credit");

            $perPage = 30;
            $page    = $request->integer('page', 1);

            $paginator = $inflows->unionAll($outflows)->unionAll($expenseRows)->unionAll($transfersOut)->unionAll($transfersIn)
                ->orderBy('date')
                ->orderBy('reference')
                ->paginate($perPage, ['*'], 'page', $page);

            $totalCredits = (float) Payment::where('payment_account_id', $paymentAccount->id)
                ->where('payable_type', Sale::class)
                ->when($dateFrom, fn($q) => $q->whereDate('payment_date', '>=', $dateFrom))
                ->when($dateTo,   fn($q) => $q->whereDate('payment_date', '<=', $dateTo))
                ->sum('amount');

            $totalDebits = (float) Payment::where('payment_account_id', $paymentAccount->id)
                ->where('payable_type', Purchase::class)
                ->when($dateFrom, fn($q) => $q->whereDate('payment_date', '>=', $dateFrom))
                ->when($dateTo,   fn($q) => $q->whereDate('payment_date', '<=', $dateTo))
                ->sum('amount')
                + (float) Expense::where('payment_account_id', $paymentAccount->id)
                ->when($dateFrom, fn($q) => $q->whereDate('expense_date', '>=', $dateFrom))
                ->when($dateTo,   fn($q) => $q->whereDate('expense_date', '<=', $dateTo))
                ->sum('amount');

            $totalTransfersIn = (float) AccountTransfer::where('to_account_id', $paymentAccount->id)
                ->when($dateFrom, fn($q) => $q->whereDate('transfer_date', '>=', $dateFrom))
                ->when($dateTo,   fn($q) => $q->whereDate('transfer_date', '<=', $dateTo))
                ->sum('amount');

            $totalTransfersOut = (float) AccountTransfer::where('from_account_id', $paymentAccount->id)
                ->when($dateFrom, fn($q) => $q->whereDate('transfer_date', '>=', $dateFrom))
                ->when($dateTo,   fn($q) => $q->whereDate('transfer_date', '<=', $dateTo))
                ->sum('amount');

            $totalCredits += $totalTransfersIn;
            $totalDebits  += $totalTransfersOut;

            return response()->json([
                'data' => $paginator->getCollection()->map(function ($row) {
                    $label = match ($row->type) {
                        'sale'         => 'Vente ' . ($row->ref_label ?? '—'),
                        'purchase'     => 'Achat ' . ($row->ref_label ?? '—'),
                        'transfer_out' => 'Transfert vers ' . ($row->ref_label ?? '—'),
                        'transfer_in'  => 'Transfert depuis ' . ($row->ref_label ?? '—'),
                        default        => 'Dépense' . ($row->ref_label ? ' — ' . $row->ref_label : ''),
                    };
                    $link = match ($row->type) {
                        'sale'     => route('pos.receipt', $row->payable_id),
                        'purchase' => route('purchases.show', $row->payable_id),
                        default    => null,
                    };
                    return [
                        'date'      => FormatHelper::date($row->date),
                        'reference' => $row->reference,
                        'label'     => $label,
                        'link'      => $link,
                        'method'    => $row->method,
                        'debit'     => (float) $row->debit,
                        'debit_display'  => $row->debit > 0 ? FormatHelper::money((float) $row->debit) : null,
                        'credit'    => (float) $row->credit,
                        'credit_display' => $row->credit > 0 ? FormatHelper::money((float) $row->credit) : null,
                    ];
                }),
                'total'          => $paginator->total(),
                'per_page'       => $paginator->perPage(),
                'current_page'   => $paginator->currentPage(),
                'last_page'      => $paginator->lastPage(),
                'from'           => $paginator->firstItem() ?? 0,
                'to'             => $paginator->lastItem() ?? 0,
                'total_credits'  => $totalCredits,
                'total_debits'   => $totalDebits,
                'total_credits_display' => FormatHelper::money($totalCredits),
                'total_debits_display'  => FormatHelper::money($totalDebits),
                'net_display'    => ($totalCredits - $totalDebits >= 0 ? '+' : '') . FormatHelper::money($totalCredits - $totalDebits),
                'net_positive'   => ($totalCredits - $totalDebits) >= 0,
            ]);
        } catch (Throwable $e) {
            $this->logError($e, 'Erreur lors du chargement du relevé du compte', ['account_id' => $paymentAccount->id]);
            return response()->json(['message' => 'Impossible de charger le relevé.'], 500);
        }
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name'            => 'required|string|max:191',
            'type'            => 'required|in:cash,bank,mobile_money',
            'opening_balance' => 'nullable|numeric|min:0',
            'account_number'  => 'nullable|string|max:100',
        ]);

        try {
            if ($request->boolean('is_default')) {
                PaymentAccount::where('is_default', true)->update(['is_default' => false]);
            }

            PaymentAccount::create(array_merge($data, [
                'current_balance' => $data['opening_balance'] ?? 0,
                'is_active'       => true,
                'is_default'      => $request->boolean('is_default'),
            ]));
        } catch (Throwable $e) {
            $this->logError($e, 'Erreur lors de la création du compte de paiement', ['data' => $data]);
            return back()->withInput()->with('error', 'Une erreur est survenue lors de la création du compte.');
        }

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

        try {
            if ($request->boolean('is_default')) {
                PaymentAccount::where('id', '!=', $paymentAccount->id)->update(['is_default' => false]);
                $data['is_default'] = true;
            }

            $paymentAccount->update($data);
        } catch (Throwable $e) {
            $this->logError($e, 'Erreur lors de la mise à jour du compte de paiement', ['account_id' => $paymentAccount->id, 'data' => $data]);
            return back()->withInput()->with('error', 'Une erreur est survenue lors de la mise à jour du compte.');
        }

        return back()->with('success', 'Compte mis à jour.');
    }

    public function destroy(PaymentAccount $paymentAccount): RedirectResponse
    {
        if ($paymentAccount->payments()->exists()
            || $paymentAccount->expenses()->exists()
            || $paymentAccount->transfersFrom()->exists()
            || $paymentAccount->transfersTo()->exists()
        ) {
            return back()->with('error', 'Impossible de supprimer ce compte : il est déjà associé à des transactions (POS, achat, dépense ou transfert).');
        }

        try {
            $paymentAccount->delete();
        } catch (Throwable $e) {
            $this->logError($e, 'Erreur lors de la suppression du compte de paiement', ['account_id' => $paymentAccount->id]);
            return back()->with('error', 'Une erreur est survenue lors de la suppression du compte.');
        }

        return back()->with('success', 'Compte supprimé.');
    }

    // ──────────────────────────────────────────────────────────────────────────
    // Transfert entre comptes
    // ──────────────────────────────────────────────────────────────────────────
    public function transfer(Request $request): RedirectResponse
    {
        // Le compte source ne doit jamais passer en négatif suite à un transfert :
        // le montant transférable va de 0 au solde actuel du compte source.
        $fromAccount = PaymentAccount::find($request->input('from_account_id'));
        $maxAmount   = $fromAccount ? max((float) $fromAccount->current_balance, 0) : 0;

        $data = $request->validate([
            'from_account_id' => 'required|exists:payment_accounts,id|different:to_account_id',
            'to_account_id'   => 'required|exists:payment_accounts,id',
            'amount'          => 'required|numeric|min:0.01|max:' . $maxAmount,
            'transfer_date'   => 'required|date',
            'note'            => 'nullable|string|max:500',
        ], [
            'amount.max' => 'Le montant ne peut pas dépasser le solde disponible du compte source (' . \App\Helpers\FormatHelper::money($maxAmount) . ').',
        ]);

        try {
            DB::transaction(function () use ($data) {
                $reference = 'TRF-' . date('Ymd') . '-' . str_pad(AccountTransfer::count() + 1, 4, '0', STR_PAD_LEFT);

                AccountTransfer::create([
                    'reference'        => $reference,
                    'from_account_id'  => $data['from_account_id'],
                    'to_account_id'    => $data['to_account_id'],
                    'amount'           => $data['amount'],
                    'transfer_date'    => $data['transfer_date'],
                    'note'             => $data['note'] ?? null,
                    'created_by'       => auth()->id(),
                ]);

                PaymentAccount::find($data['from_account_id'])?->decrement('current_balance', $data['amount']);
                PaymentAccount::find($data['to_account_id'])?->increment('current_balance', $data['amount']);
            });
        } catch (Throwable $e) {
            $this->logError($e, 'Erreur lors du transfert entre comptes', ['data' => $data]);
            return back()->withInput()->with('error', 'Une erreur est survenue lors du transfert.');
        }

        return back()->with('success', 'Transfert effectué avec succès.');
    }

    // ──────────────────────────────────────────────────────────────────────────
    // Bilan
    // ──────────────────────────────────────────────────────────────────────────
    public function balanceSheet(Request $request): View|RedirectResponse
    {
        try {
            $asOfDate = $request->input('date', now()->toDateString());

            // Actif — Trésorerie recalculée à la date choisie (solde initial +
            // encaissements − décaissements jusqu'à cette date), même logique
            // que la balance de vérification — pour que le Bilan reste exact à
            // une date passée, pas seulement "aujourd'hui".
            $accounts = PaymentAccount::where('is_active', true)->get()->map(function (PaymentAccount $acc) use ($asOfDate) {
                $encaissements = Payment::where('payment_account_id', $acc->id)
                    ->where('payable_type', Sale::class)
                    ->where('payment_date', '<=', $asOfDate)
                    ->sum('amount')
                    + AccountTransfer::where('to_account_id', $acc->id)
                        ->where('transfer_date', '<=', $asOfDate)
                        ->sum('amount');

                $decaissements = Payment::where('payment_account_id', $acc->id)
                    ->where('payable_type', Purchase::class)
                    ->where('payment_date', '<=', $asOfDate)
                    ->sum('amount')
                    + Expense::where('payment_account_id', $acc->id)
                        ->where('expense_date', '<=', $asOfDate)
                        ->sum('amount')
                    + AccountTransfer::where('from_account_id', $acc->id)
                        ->where('transfer_date', '<=', $asOfDate)
                        ->sum('amount');

                $acc->balance_as_of = (float) $acc->opening_balance + $encaissements - $decaissements;

                return $acc;
            });

            $totalTresorerie = $accounts->sum('balance_as_of');

            // Actif — Valeur du stock (coût d'achat actuel). L'historique des prix
            // d'achat n'est pas conservé : contrairement à la trésorerie et aux
            // créances/dettes, cette valeur n'est pas recalculée à la date choisie.
            $stockValue = Product::whereNull('deleted_at')
                ->selectRaw('SUM(stock_quantity * buying_price) as total')
                ->value('total') ?? 0;

            // Actif — Créances clients à la date choisie : ventes confirmées
            // créées avant/à cette date, moins les paiements reçus avant/à cette date.
            $creancesClients = (float) DB::table('sales')
                ->where('sales.status', 'confirmed')
                ->where('sales.sale_date', '<=', $asOfDate)
                ->whereNull('sales.deleted_at')
                ->selectRaw("COALESCE(SUM(sales.total - COALESCE((
                    SELECT SUM(p.amount) FROM payments p
                    WHERE p.payable_type = ? AND p.payable_id = sales.id AND p.payment_date <= ?
                ), 0)), 0) as total_due", [Sale::class, $asOfDate])
                ->value('total_due');

            $totalActif = $totalTresorerie + $stockValue + $creancesClients;

            // Passif — Dettes fournisseurs à la date choisie, même logique.
            $dettesFournisseurs = (float) DB::table('purchases')
                ->where('purchases.status', 'confirmed')
                ->where('purchases.purchase_date', '<=', $asOfDate)
                ->whereNull('purchases.deleted_at')
                ->selectRaw("COALESCE(SUM(purchases.total - COALESCE((
                    SELECT SUM(p.amount) FROM payments p
                    WHERE p.payable_type = ? AND p.payable_id = purchases.id AND p.payment_date <= ?
                ), 0)), 0) as total_due", [Purchase::class, $asOfDate])
                ->value('total_due');

            $totalPassif   = $dettesFournisseurs;
            $situationNette = $totalActif - $totalPassif;

            return view('pages.accounts.balance-sheet', compact(
                'accounts', 'totalTresorerie', 'stockValue', 'creancesClients',
                'totalActif', 'dettesFournisseurs', 'totalPassif', 'situationNette', 'asOfDate'
            ));
        } catch (Throwable $e) {
            $this->logError($e, 'Erreur lors du chargement du bilan', ['date' => $request->input('date')]);
            return redirect()->route('payment-accounts.index')->with('error', 'Une erreur est survenue lors du chargement du bilan.');
        }
    }

    // ──────────────────────────────────────────────────────────────────────────
    // Balance de vérification
    // ──────────────────────────────────────────────────────────────────────────
    public function trialBalance(Request $request): View|RedirectResponse
    {
        try {
            $year  = (int) $request->input('year', now()->year);
            $start = "$year-01-01";
            $end   = "$year-12-31";

            $accounts = PaymentAccount::all()->map(function (PaymentAccount $acc) use ($start, $end) {
                // Encaissements = paiements de ventes + transferts reçus d'un autre compte
                $encVentes = Payment::where('payment_account_id', $acc->id)
                    ->where('payable_type', Sale::class)
                    ->whereBetween('payment_date', [$start, $end])
                    ->sum('amount');

                $transfertsRecus = AccountTransfer::where('to_account_id', $acc->id)
                    ->whereBetween('transfer_date', [$start, $end])
                    ->sum('amount');

                $encaissements = $encVentes + $transfertsRecus;

                // Décaissements = paiements d'achats + dépenses + transferts envoyés
                $decAchats = Payment::where('payment_account_id', $acc->id)
                    ->where('payable_type', Purchase::class)
                    ->whereBetween('payment_date', [$start, $end])
                    ->sum('amount');

                $decDepenses = Expense::where('payment_account_id', $acc->id)
                    ->whereBetween('expense_date', [$start, $end])
                    ->sum('amount');

                $transfertsEnvoyes = AccountTransfer::where('from_account_id', $acc->id)
                    ->whereBetween('transfer_date', [$start, $end])
                    ->sum('amount');

                $decaissements = $decAchats + $decDepenses + $transfertsEnvoyes;

                return (object) [
                    'name'              => $acc->name,
                    'type'              => $acc->type,
                    'opening'           => (float) $acc->opening_balance,
                    'encaissements'     => (float) $encaissements,
                    'decaissements'     => (float) $decaissements,
                    'dec_achats'        => (float) $decAchats,
                    'dec_depenses'      => (float) $decDepenses,
                    'transferts_net'    => (float) ($transfertsRecus - $transfertsEnvoyes),
                    'solde_calcule'     => (float) $acc->opening_balance + $encaissements - $decaissements,
                    'solde_reel'        => (float) $acc->current_balance,
                ];
            });

            $years = range(now()->year, max(now()->year - 4, 2024));

            return view('pages.accounts.trial-balance', compact('accounts', 'year', 'years'));
        } catch (Throwable $e) {
            $this->logError($e, 'Erreur lors du chargement de la balance de vérification', ['year' => $request->input('year')]);
            return redirect()->route('payment-accounts.index')->with('error', 'Une erreur est survenue lors du chargement de la balance.');
        }
    }

    // ──────────────────────────────────────────────────────────────────────────
    // Flux de trésorerie
    // ──────────────────────────────────────────────────────────────────────────
    public function cashFlow(Request $request): View|RedirectResponse
    {
        try {
            $year  = (int) $request->input('year', now()->year);
            $years = range(now()->year, max(now()->year - 4, 2024));

            // Solde d'ouverture au 1er janvier de l'année sélectionnée : solde
            // d'ouverture de tous les comptes + net de tous les flux antérieurs
            // à cette année — pour que le solde cumulé reste juste peu importe
            // l'année choisie dans le sélecteur.
            $yearStart = sprintf('%d-01-01', $year);

            $openingBalance    = (float) PaymentAccount::sum('opening_balance');
            $priorEncaissements = (float) Payment::where('payable_type', Sale::class)
                ->where('payment_date', '<', $yearStart)->sum('amount');
            $priorDecaissements = (float) Payment::where('payable_type', Purchase::class)
                ->where('payment_date', '<', $yearStart)->sum('amount')
                + (float) Expense::where('expense_date', '<', $yearStart)->sum('amount');

            $cumBalance = $openingBalance + $priorEncaissements - $priorDecaissements;
            $openingCumBalance = $cumBalance;

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
                $net           = (float) $encaissements - $decaissements;
                $cumBalance   += $net;

                $months[] = (object) [
                    'label'         => \Carbon\Carbon::create($year, $m)->isoFormat('MMM'),
                    'encaissements' => (float) $encaissements,
                    'dec_achats'    => (float) $decAchats,
                    'dec_depenses'  => (float) $decDepenses,
                    'decaissements' => (float) $decaissements,
                    'net'           => $net,
                    'cum_balance'   => $cumBalance,
                ];
            }

            $totals = (object) [
                'encaissements'      => collect($months)->sum('encaissements'),
                'dec_achats'         => collect($months)->sum('dec_achats'),
                'dec_depenses'       => collect($months)->sum('dec_depenses'),
                'decaissements'      => collect($months)->sum('decaissements'),
                'net'                => collect($months)->sum('net'),
                'opening_balance'    => $openingCumBalance,
                'closing_balance'    => $cumBalance,
            ];

            return view('pages.accounts.cash-flow', compact('months', 'totals', 'year', 'years'));
        } catch (Throwable $e) {
            $this->logError($e, 'Erreur lors du chargement du flux de trésorerie', ['year' => $request->input('year')]);
            return redirect()->route('payment-accounts.index')->with('error', 'Une erreur est survenue lors du chargement du flux de trésorerie.');
        }
    }
}
