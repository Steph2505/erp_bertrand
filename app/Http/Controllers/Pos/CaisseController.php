<?php

namespace App\Http\Controllers\Pos;

use App\Http\Controllers\Controller;
use App\Models\Caisse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CaisseController extends Controller
{
    public function index(Request $request): View
    {
        $userId = auth()->id();

        $caisses = Caisse::withCount('sessions')
            ->with(['sessions' => fn($q) => $q->whereNull('closed_at')->with('user')->orderByDesc('opened_at')])
            ->orderBy('name')
            ->get();

        $openSessions = \App\Models\PosSession::whereNull('closed_at')
            ->with(['caisse', 'user', 'warehouse'])
            ->orderByDesc('opened_at')
            ->get();

        $warehouses = \App\Models\Warehouse::where('is_active', true)->orderBy('name')->get(['id', 'name']);

        return view('pages.pos.caisses', compact('caisses', 'warehouses', 'userId', 'openSessions'));
    }

    public function search(Request $request): \Illuminate\Http\JsonResponse
    {
        $q      = $request->get('q', '');
        $userId = auth()->id();

        $caisses = Caisse::withCount('sessions')
            ->with(['sessions' => fn($query) => $query->whereNull('closed_at')->with('user')->orderByDesc('opened_at')])
            ->when($q, fn($query) => $query->where(function ($query) use ($q) {
                $query->where('name', 'like', "%{$q}%")
                      ->orWhere('description', 'like', "%{$q}%");
            }))
            ->orderBy('name')
            ->get()
            ->map(fn($c) => [
                'id'             => $c->id,
                'name'           => $c->name,
                'description'    => $c->description,
                'is_active'      => $c->is_active,
                'sessions_count' => $c->sessions_count,
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
            ]);

        return response()->json(['caisses' => $caisses, 'total' => $caisses->count()]);
    }

    public function show(Caisse $caisse): View
    {
        $caisse->load([
            'sessions' => fn($q) => $q->with('user', 'warehouse')->orderByDesc('opened_at'),
        ]);

        return view('pages.pos.caisse-show', compact('caisse'));
    }

    public function store(Request $request): RedirectResponse|\Illuminate\Http\JsonResponse
    {
        $request->validate([
            'name'        => 'required|string|max:100|unique:caisses,name',
            'description' => 'nullable|string|max:255',
        ]);

        $caisse = Caisse::create([
            'name'        => $request->name,
            'description' => $request->description,
            'is_active'   => true,
        ]);

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
        ]);

        $caisse->update([
            'name'        => $request->name,
            'description' => $request->description,
        ]);

        return back()->with('success', 'Caisse mise à jour.');
    }

    public function toggleActive(Caisse $caisse): RedirectResponse
    {
        $caisse->update(['is_active' => !$caisse->is_active]);
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

        $caisse->delete();
        return back()->with('success', 'Caisse supprimée.');
    }
}
