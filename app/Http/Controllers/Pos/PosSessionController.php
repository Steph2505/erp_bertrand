<?php

namespace App\Http\Controllers\Pos;

use App\Http\Controllers\Controller;
use App\Models\PosSession;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Throwable;

class PosSessionController extends Controller
{
    public function index(): View|RedirectResponse
    {
        try {
            return view('pages.pos.sessions');
        } catch (Throwable $e) {
            $this->logError($e, 'Erreur lors du chargement de la page des sessions de caisse');
            return redirect()->route('dashboard')->with('error', 'Une erreur est survenue lors du chargement de la page.');
        }
    }

    public function apiIndex(Request $request): JsonResponse
    {
        try {
            $paginator = PosSession::with(['user', 'warehouse', 'caisse'])
                ->orderByDesc('opened_at')
                ->paginate(10, ['*'], 'page', $request->integer('page', 1));

            return response()->json([
                'data' => $paginator->getCollection()->map(fn($s) => [
                    'id'               => $s->id,
                    'caissier'         => $s->user->name,
                    'caisse'           => $s->caisse?->name ?? '—',
                    'warehouse'        => $s->warehouse?->name ?? '—',
                    'date'             => $s->opened_at->format('d/m/Y'),
                    'opened_at'        => $s->opened_at->format('H:i'),
                    'opening_balance'  => \App\Helpers\FormatHelper::money($s->opening_balance),
                    'total_sales'      => \App\Helpers\FormatHelper::money($s->total_sales),
                    'closed_at'        => $s->closed_at ? $s->closed_at->format('H:i') : null,
                    'closing_balance'  => $s->closing_balance !== null ? \App\Helpers\FormatHelper::money($s->closing_balance) : null,
                    'is_open'          => $s->isOpen(),
                    'show_url'         => route('pos.sessions.show', $s->id),
                    'close_url'        => route('pos.sessions.close', $s->id),
                ]),
                'total'        => $paginator->total(),
                'per_page'     => $paginator->perPage(),
                'current_page' => $paginator->currentPage(),
                'last_page'    => $paginator->lastPage(),
                'from'         => $paginator->firstItem() ?? 0,
                'to'           => $paginator->lastItem() ?? 0,
            ]);
        } catch (Throwable $e) {
            $this->logError($e, 'Erreur lors du chargement des sessions de caisse', ['filters' => $request->all()]);
            return response()->json(['message' => 'Impossible de charger les sessions.'], 500);
        }
    }

    public function open(Request $request): RedirectResponse
    {
        $request->validate([
            'caisse_id'       => 'required|exists:caisses,id',
            'opening_balance' => 'required|numeric|min:0',
            'warehouse_id'    => 'nullable|exists:warehouses,id',
        ]);

        try {
            DB::transaction(function () use ($request) {
                // Fermer toute session ouverte de cet utilisateur
                PosSession::where('user_id', auth()->id())
                    ->whereNull('closed_at')
                    ->update(['closed_at' => now()]);

                PosSession::create([
                    'caisse_id'       => $request->caisse_id,
                    'user_id'         => auth()->id(),
                    'warehouse_id'    => $request->warehouse_id,
                    'opening_balance' => $request->opening_balance,
                    'total_sales'     => 0,
                    'opened_at'       => now(),
                ]);
            });
        } catch (Throwable $e) {
            $this->logError($e, 'Erreur lors de l\'ouverture de la caisse', ['request' => $request->except('_token')]);
            return back()->withInput()->with('error', 'Une erreur est survenue lors de l\'ouverture de la caisse.');
        }

        return redirect()->route('pos.index')->with('success', 'Caisse ouverte avec succès.');
    }

    public function close(Request $request, PosSession $session): RedirectResponse
    {
        $request->validate([
            'closing_balance' => 'required|numeric|min:0',
            'note'            => 'nullable|string|max:500',
        ]);

        try {
            $session->update([
                'closing_balance' => $request->closing_balance,
                'note'            => $request->note,
                'closed_at'       => now(),
            ]);
        } catch (Throwable $e) {
            $this->logError($e, 'Erreur lors de la clôture de la caisse', ['session_id' => $session->id]);
            return back()->withInput()->with('error', 'Une erreur est survenue lors de la clôture de la caisse.');
        }

        return redirect()->route('pos.sessions.index')
            ->with('success', 'Caisse clôturée avec succès.');
    }

    public function show(PosSession $session): View|RedirectResponse
    {
        try {
            $session->load(['user', 'warehouse', 'caisse', 'sales.customer', 'sales.items']);

            return view('pages.pos.session-show', compact('session'));
        } catch (Throwable $e) {
            $this->logError($e, 'Erreur lors du chargement de la session de caisse', ['session_id' => $session->id]);
            return redirect()->route('pos.sessions.index')->with('error', 'Une erreur est survenue lors du chargement de la session.');
        }
    }
}
