<?php

namespace App\Http\Controllers\Purchases;

use App\Http\Controllers\Controller;
use App\Models\Purchase;
use App\Models\PurchaseReturn;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Throwable;

class PurchaseReturnController extends Controller
{
    public function index(): View|RedirectResponse
    {
        try {
            $returns = PurchaseReturn::with(['purchase.supplier', 'createdBy'])
                ->orderByDesc('return_date')
                ->paginate(20);

            return view('pages.purchases.returns', compact('returns'));
        } catch (Throwable $e) {
            $this->logError($e, 'Erreur lors du chargement de la page des retours d\'achat');
            return redirect()->route('dashboard')->with('error', 'Une erreur est survenue lors du chargement de la page.');
        }
    }

    public function create(Request $request): View|RedirectResponse
    {
        try {
            $purchase = null;
            if ($request->purchase_id) {
                $purchase = Purchase::with(['supplier', 'items'])->findOrFail($request->purchase_id);
            }

            $purchases = Purchase::with('supplier')
                ->where('status', 'confirmed')
                ->orderByDesc('purchase_date')
                ->get()
                ->map(fn($p) => [
                    'id'       => $p->id,
                    'label'    => $p->reference . ' — ' . ($p->supplier?->name ?? 'Sans fournisseur'),
                    'ref'      => $p->reference,
                    'supplier' => $p->supplier?->name ?? '—',
                    'date'     => $p->purchase_date->format('d/m/Y'),
                    'total'    => (float) $p->total,
                ]);

            return view('pages.purchases.return-create', compact('purchase', 'purchases'));
        } catch (Throwable $e) {
            $this->logError($e, 'Erreur lors du chargement du formulaire de retour d\'achat', ['purchase_id' => $request->purchase_id]);
            return redirect()->route('purchase-returns.index')->with('error', 'Une erreur est survenue lors du chargement de la page.');
        }
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'purchase_id' => 'required|exists:purchases,id',
            'return_date' => 'required|date',
            'total'       => 'required|numeric|min:0.01',
            'reason'      => 'nullable|string|max:500',
        ]);

        $purchase = Purchase::findOrFail($request->purchase_id);

        if ($request->total > $purchase->total) {
            return back()
                ->withErrors(['total' => 'Le montant retourné ne peut pas dépasser le total de l\'achat.'])
                ->withInput();
        }

        try {
            $ref = 'RET-ACH-' . date('Ymd') . '-' . str_pad(
                PurchaseReturn::whereDate('created_at', today())->count() + 1,
                4, '0', STR_PAD_LEFT
            );

            PurchaseReturn::create([
                'reference'   => $ref,
                'purchase_id' => $request->purchase_id,
                'return_date' => $request->return_date,
                'total'       => $request->total,
                'reason'      => $request->reason,
                'created_by'  => auth()->id(),
            ]);
        } catch (Throwable $e) {
            $this->logError($e, 'Erreur lors de la création du retour d\'achat', ['request' => $request->except('_token')]);
            return back()->withInput()->with('error', 'Une erreur est survenue lors de l\'enregistrement du retour.');
        }

        return redirect()->route('purchase-returns.index')->with('success', 'Retour enregistré avec succès.');
    }
}
