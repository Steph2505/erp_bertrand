<?php

namespace App\Http\Controllers\Pos;

use App\Http\Controllers\Controller;
use App\Models\Caisse;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Throwable;

class CaisseController extends Controller
{
    public function index(Request $request): View|RedirectResponse
    {
        try {
            $user   = $request->user();
            $userId = $user->id;

            $caisses = Caisse::withCount('sessions')
                ->with(['manager', 'sessions' => fn($q) => $q->whereNull('closed_at')->with('user')->orderByDesc('opened_at')])
                ->orderBy('name')
                ->get()
                ->filter(fn($c) => $c->canBeOpenedBy($user))
                ->values();

            $openSessions = \App\Models\PosSession::whereNull('closed_at')
                ->with(['caisse', 'user', 'warehouse'])
                ->orderByDesc('opened_at')
                ->get();

            $warehouses = \App\Models\Warehouse::where('is_active', true)->orderBy('name')->get(['id', 'name']);
            $managers   = User::where('is_active', true)->permission('manage pos')->orderBy('name')->get(['id', 'name']);

            return view('pages.pos.caisses', compact('caisses', 'warehouses', 'userId', 'openSessions', 'managers'));
        } catch (Throwable $e) {
            $this->logError($e, 'Erreur lors du chargement de la page des caisses');
            return redirect()->route('dashboard')->with('error', 'Une erreur est survenue lors du chargement de la page.');
        }
    }

    public function search(Request $request): JsonResponse
    {
        try {
            $q      = $request->get('q', '');
            $user   = $request->user();
            $userId = $user->id;

            $caisses = Caisse::withCount('sessions')
                ->with(['manager', 'sessions' => fn($query) => $query->whereNull('closed_at')->with('user')->orderByDesc('opened_at')])
                ->when($q, fn($query) => $query->where(function ($query) use ($q) {
                    $query->where('name', 'like', "%{$q}%")
                          ->orWhere('description', 'like', "%{$q}%");
                }))
                ->orderBy('name')
                ->get()
                ->filter(fn($c) => $c->canBeOpenedBy($user))
                ->map(fn($c) => [
                    'id'             => $c->id,
                    'name'           => $c->name,
                    'description'    => $c->description,
                    'is_active'      => $c->is_active,
                    'sessions_count' => $c->sessions_count,
                    'manager_id'     => $c->manager_id,
                    'manager_name'   => $c->manager?->name,
                    'my_session'     => $c->sessions->firstWhere('user_id', $userId) ? true : false,
                    'open_session'   => $c->sessions->first() ? [
                        'user_name'  => $c->sessions->first()->user->name,
                        'opened_at'  => $c->sessions->first()->opened_at->format('H:i'),
                        'diff'       => $c->sessions->first()->opened_at->diffForHumans(),
                    ] : null,
                    'show_url'       => route('pos.caisses.show', $c->id),
                    'toggle_url'     => route('pos.caisses.toggle', $c->id),
                    'destroy_url'    => route('pos.caisses.destroy', $c->id),
                    'update_url'     => route('pos.caisses.update', $c->id),
                ])
                ->values();

            return response()->json(['caisses' => $caisses, 'total' => $caisses->count()]);
        } catch (Throwable $e) {
            $this->logError($e, 'Erreur lors de la recherche de caisses', ['query' => $request->get('q')]);
            return response()->json(['message' => 'Une erreur est survenue lors de la recherche.'], 500);
        }
    }

    public function show(Caisse $caisse): View|RedirectResponse
    {
        try {
            $caisse->load([
                'sessions' => fn($q) => $q->with('user', 'warehouse')->orderByDesc('opened_at'),
            ]);

            return view('pages.pos.caisse-show', compact('caisse'));
        } catch (Throwable $e) {
            $this->logError($e, 'Erreur lors du chargement de la caisse', ['caisse_id' => $caisse->id]);
            return redirect()->route('pos.caisses.index')->with('error', 'Une erreur est survenue lors du chargement de la caisse.');
        }
    }

    public function store(Request $request): RedirectResponse|JsonResponse
    {
        $request->validate([
            'name'        => 'required|string|max:100|unique:caisses,name',
            'description' => 'nullable|string|max:255',
            'manager_id'  => 'nullable|exists:users,id',
        ]);

        // Un Admin/Super Admin choisit librement le gérant (ou aucun = accès
        // réservé aux admins). Un autre rôle ne peut créer une caisse que pour
        // lui-même : il en devient automatiquement le gérant.
        $isAdmin   = $request->user()->hasAnyRole(['Admin', 'Super Admin']);
        $managerId = $isAdmin ? ($request->manager_id ?: null) : $request->user()->id;

        try {
            $caisse = Caisse::create([
                'name'        => $request->name,
                'description' => $request->description,
                'manager_id'  => $managerId,
                'is_active'   => true,
            ]);
        } catch (Throwable $e) {
            $this->logError($e, 'Erreur lors de la création de la caisse', ['name' => $request->name]);

            if ($request->wantsJson()) {
                return response()->json(['message' => 'Une erreur est survenue lors de la création de la caisse.'], 500);
            }
            return back()->withInput()->with('error', 'Une erreur est survenue lors de la création de la caisse.');
        }

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'caisse'  => ['id' => $caisse->id, 'name' => $caisse->name, 'is_active' => true],
            ]);
        }

        return back()->with('success', 'Caisse créée avec succès.');
    }

    public function update(Request $request, Caisse $caisse): RedirectResponse
    {
        $request->validate([
            'name'        => 'required|string|max:100|unique:caisses,name,' . $caisse->id,
            'description' => 'nullable|string|max:255',
            'manager_id'  => 'nullable|exists:users,id',
        ]);

        // Seul un Admin/Super Admin peut changer le gérant assigné. Un autre
        // rôle qui modifie une caisse ne peut pas se retirer ni réassigner
        // la gestion à quelqu'un d'autre.
        $isAdmin   = $request->user()->hasAnyRole(['Admin', 'Super Admin']);
        $managerId = $isAdmin ? ($request->manager_id ?: null) : $caisse->manager_id;

        try {
            $caisse->update([
                'name'        => $request->name,
                'description' => $request->description,
                'manager_id'  => $managerId,
            ]);
        } catch (Throwable $e) {
            $this->logError($e, 'Erreur lors de la mise à jour de la caisse', ['caisse_id' => $caisse->id]);
            return back()->withInput()->with('error', 'Une erreur est survenue lors de la mise à jour de la caisse.');
        }

        return back()->with('success', 'Caisse mise à jour.');
    }

    public function toggleActive(Caisse $caisse): RedirectResponse
    {
        try {
            $caisse->update(['is_active' => !$caisse->is_active]);
        } catch (Throwable $e) {
            $this->logError($e, 'Erreur lors du changement d\'état de la caisse', ['caisse_id' => $caisse->id]);
            return back()->with('error', 'Une erreur est survenue.');
        }

        return back()->with('success', $caisse->is_active ? 'Caisse activée.' : 'Caisse désactivée.');
    }

    public function destroy(Caisse $caisse): RedirectResponse
    {
        // Session ouverte en cours — blocage strict
        if ($caisse->sessions()->whereNull('closed_at')->exists()) {
            return back()->withErrors(['caisse' => 'Impossible : cette caisse a une session ouverte. Clôturez-la d\'abord.']);
        }

        // Historique de sessions (tickets de caisse) — protéger la traçabilité financière
        if ($caisse->sessions()->exists()) {
            return back()->withErrors(['caisse' => 'Impossible de supprimer cette caisse : elle possède un historique de sessions et de tickets. Désactivez-la à la place.']);
        }

        try {
            $caisse->delete();
        } catch (Throwable $e) {
            $this->logError($e, 'Erreur lors de la suppression de la caisse', ['caisse_id' => $caisse->id]);
            return back()->with('error', 'Une erreur est survenue lors de la suppression de la caisse.');
        }

        return back()->with('success', 'Caisse supprimée.');
    }
}
