<?php

namespace App\Http\Controllers\Pos;

use App\Http\Controllers\Controller;
use App\Models\PosSession;
use App\Models\User;
use App\Notifications\PosSessionClosedAlert;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;
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
                ->when(
                    ! $request->user()->hasAnyRole(['Admin', 'Super Admin']),
                    fn($q) => $q->where('user_id', $request->user()->id)
                        ->whereHas('caisse', fn($caisseQuery) => $caisseQuery
                            ->where('manager_id', $request->user()->id)
                            ->orWhereHas('authorizedUsers', fn($userQuery) => $userQuery->whereKey($request->user()->id)))
                )
                ->orderByDesc('opened_at')
                ->paginate(10, ['*'], 'page', $request->integer('page', 1));

            return response()->json([
                'data' => $paginator->getCollection()->map(fn($s) => [
                    'id'               => $s->id,
                    'caissier'         => $s->user->name,
                    'caisse'           => $s->caisse?->name ?? '—',
                    'warehouse'        => $s->warehouse?->name ?? '—',
                    'date'             => $s->opened_at->format('d/m/Y'),
                    'opened_at'        => \App\Helpers\FormatHelper::datetime($s->opened_at),
                    'opening_balance'  => \App\Helpers\FormatHelper::money($s->opening_balance),
                    'total_sales'      => \App\Helpers\FormatHelper::money($s->total_sales),
                    'closed_at'        => $s->closed_at ? \App\Helpers\FormatHelper::datetime($s->closed_at) : null,
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

    public function open(Request $request): RedirectResponse|JsonResponse
    {
        $request->validate([
            'caisse_id'       => 'required|exists:caisses,id',
            'opening_balance' => 'required|numeric|min:0',
            'warehouse_id'    => 'nullable|exists:warehouses,id',
        ]);

        $caisse = \App\Models\Caisse::find($request->caisse_id);
        if (! $caisse->canBeOpenedBy($request->user())) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Cette caisse ne vous est pas attribuée.'], 403);
            }
            return back()->withInput()->with('error', 'Cette caisse est assignée à un autre utilisateur.');
        }

        $isAdmin = $request->user()->hasAnyRole(['Admin', 'Super Admin']);
        if (! $isAdmin) {
            $request->validate(['pin' => 'required|digits:6']);
        }

        try {
            $result = DB::transaction(function () use ($request, $caisse, $isAdmin) {
                $user = User::whereKey($request->user()->id)->lockForUpdate()->firstOrFail();

                if (! $isAdmin) {
                    $pinError = $this->verifyOrSetPosPin($request, $user);
                    if ($pinError) {
                        return $pinError;
                    }
                }

                // Fermer toute session ouverte de cet utilisateur
                PosSession::where('user_id', $user->id)
                    ->whereNull('closed_at')
                    ->update(['closed_at' => now()]);

                $session = PosSession::create([
                    'caisse_id'       => $caisse->id,
                    'user_id'         => $user->id,
                    'warehouse_id'    => $request->warehouse_id,
                    'opening_balance' => $request->opening_balance,
                    'total_sales'     => 0,
                    'opened_at'       => now(),
                ]);

                return ['session' => $session];
            });

            if (isset($result['error'])) {
                return response()->json(['message' => $result['error']], $result['status']);
            }
        } catch (ValidationException $e) {
            throw $e;
        } catch (Throwable $e) {
            $this->logError($e, 'Erreur lors de l\'ouverture de la caisse', ['request' => $request->except(['_token', 'pin', 'pin_confirmation'])]);
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Une erreur est survenue lors de l\'ouverture de la caisse.'], 500);
            }
            return back()->withInput()->with('error', 'Une erreur est survenue lors de l\'ouverture de la caisse.');
        }

        if (! $isAdmin) {
            $request->session()->flash('pos_access_verified_for', $result['session']->id);
        }

        if ($request->expectsJson()) {
            return response()->json(['success' => true, 'redirect' => route('pos.index')]);
        }

        return redirect()->route('pos.index')->with('success', 'Caisse ouverte avec succès.');
    }

    public function verifyReturnAccessPin(Request $request, PosSession $session): JsonResponse
    {
        $this->authorizeReturnAccess($request, $session);
        $request->validate(['pin' => 'required|digits:6']);

        $error = DB::transaction(function () use ($request) {
            $user = User::whereKey($request->user()->id)->lockForUpdate()->firstOrFail();

            return $this->verifyOrSetPosPin($request, $user);
        });

        if ($error) {
            return response()->json(['message' => $error['error']], $error['status']);
        }

        $request->session()->flash('pos_access_verified_for', $session->id);

        return response()->json(['success' => true, 'redirect' => route('pos.index')]);
    }

    private function authorizeReturnAccess(Request $request, PosSession $session): void
    {
        abort_unless(
            $session->isOpen()
            && $session->user_id === $request->user()->id
            && $session->caisse?->canBeOpenedBy($request->user()),
            404
        );
    }

    private function verifyOrSetPosPin(Request $request, User $user): ?array
    {
        if (! $user->pos_pin_hash) {
            $request->validate(['pin_confirmation' => 'required|same:pin']);
            $user->forceFill(['pos_pin_hash' => Hash::make($request->input('pin'))])->save();

            return null;
        }

        $key = 'pos-pin:' . $user->id;
        if (RateLimiter::tooManyAttempts($key, 5)) {
            return [
                'error' => 'Trop de tentatives. Réessayez dans ' . RateLimiter::availableIn($key) . ' secondes.',
                'status' => 429,
            ];
        }

        if (! Hash::check($request->input('pin'), $user->pos_pin_hash)) {
            RateLimiter::hit($key, 60);

            return ['error' => 'PIN incorrect.', 'status' => 422];
        }

        RateLimiter::clear($key);

        return null;
    }

    public function close(Request $request, PosSession $session): Response
    {
        $user = $request->user();
        $isAdmin = $user->hasAnyRole(['Admin', 'Super Admin']);

        if (! $isAdmin && ! $user->can('close pos sessions')) {
            abort(403, 'Vous n’avez pas le droit de clôturer une session de caisse.');
        }

        if ($session->user_id !== $user->id && ! $isAdmin) {
            abort(403, 'Vous ne pouvez clôturer que vos propres sessions de caisse.');
        }

        try {
            $session->update([
                'closing_balance' => $session->expected_closing,
                'closed_at'       => now(),
            ]);

            $session->load([
                'caisse',
                'warehouse',
                'user',
                'sales.customer',
                'sales.items',
                'sales.payments.paymentAccount',
            ]);

            $recipients = User::permission('receive notifications')
                ->where('is_active', true)
                ->where('id', '!=', auth()->id())
                ->get();

            if ($recipients->isNotEmpty()) {
                Notification::send($recipients, new PosSessionClosedAlert($session));
            }

            $filename = 'rapport-caisse-' . $session->id . '-' . $session->closed_at->format('Ymd-His') . '.pdf';

            return Pdf::loadView('pages.pos.session-report', compact('session'))
                ->setPaper('a4')
                ->download($filename);
        } catch (Throwable $e) {
            $this->logError($e, 'Erreur lors de la clôture de la caisse', ['session_id' => $session->id]);
            return back()->withInput()->with('error', 'Une erreur est survenue lors de la clôture de la caisse.');
        }
    }

    public function show(PosSession $session): View|RedirectResponse
    {
        $user = auth()->user();
        if (! $user->hasAnyRole(['Admin', 'Super Admin'])
            && ($session->user_id !== $user->id || ! $session->caisse?->canBeOpenedBy($user))) {
            abort(404);
        }

        try {
            $session->load(['user', 'warehouse', 'caisse', 'sales.customer', 'sales.items']);

            return view('pages.pos.session-show', compact('session'));
        } catch (Throwable $e) {
            $this->logError($e, 'Erreur lors du chargement de la session de caisse', ['session_id' => $session->id]);
            return redirect()->route('pos.sessions.index')->with('error', 'Une erreur est survenue lors du chargement de la session.');
        }
    }
}
