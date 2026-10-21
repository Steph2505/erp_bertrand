<?php

namespace App\Http\Controllers\Pos;

use App\Http\Controllers\Controller;
use App\Models\PosSession;
use App\Models\PosSessionOpeningCode;
use App\Models\User;
use App\Notifications\PosSessionClosedAlert;
use App\Notifications\PosSessionOpeningCodeNotification;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
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
            'verification_code' => 'nullable|digits:6',
        ]);

        $caisse = \App\Models\Caisse::find($request->caisse_id);
        if (! $caisse->canBeOpenedBy($request->user())) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Cette caisse ne vous est pas attribuée.'], 403);
            }
            return back()->withInput()->with('error', 'Cette caisse est assignée à un autre utilisateur.');
        }

        $isAdmin = $request->user()->hasAnyRole(['Admin', 'Super Admin']);
        if (! $isAdmin && ! $request->filled('verification_code')) {
            return $this->sendOpeningCode($request, $caisse);
        }

        try {
            $result = DB::transaction(function () use ($request, $caisse, $isAdmin) {
                if (! $isAdmin) {
                    $challenge = PosSessionOpeningCode::where('user_id', $request->user()->id)
                        ->where('caisse_id', $caisse->id)
                        ->lockForUpdate()
                        ->first();

                    if (! $challenge || $challenge->consumed_at || $challenge->expires_at->isPast()) {
                        return ['error' => 'Code expiré ou introuvable. Demandez un nouveau code.', 'status' => 422];
                    }

                    if ($challenge->attempts >= 5) {
                        return ['error' => 'Trop de tentatives. Demandez un nouveau code.', 'status' => 422];
                    }

                    if (! Hash::check($request->verification_code, $challenge->code_hash)) {
                        $challenge->increment('attempts');

                        return ['error' => 'Code incorrect. Vérifiez le code reçu par mail.', 'status' => 422];
                    }

                    $challenge->update(['consumed_at' => now(), 'attempts' => 0]);
                }

                // Fermer toute session ouverte de cet utilisateur
                PosSession::where('user_id', $request->user()->id)
                    ->whereNull('closed_at')
                    ->update(['closed_at' => now()]);

                $session = PosSession::create([
                    'caisse_id'       => $caisse->id,
                    'user_id'         => $request->user()->id,
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
        } catch (Throwable $e) {
            $this->logError($e, 'Erreur lors de l\'ouverture de la caisse', ['request' => $request->except(['_token', 'verification_code'])]);
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Une erreur est survenue lors de l\'ouverture de la caisse.'], 500);
            }
            return back()->withInput()->with('error', 'Une erreur est survenue lors de l\'ouverture de la caisse.');
        }

        if ($request->expectsJson()) {
            if (! $isAdmin) {
                $request->session()->flash('pos_access_verified_for', $result['session']->id);
            }

            return response()->json(['success' => true, 'redirect' => route('pos.index')]);
        }

        return redirect()->route('pos.index')->with('success', 'Caisse ouverte avec succès.');
    }

    public function verifyReturnAccessCode(Request $request, PosSession $session): JsonResponse
    {
        $this->authorizeReturnAccess($request, $session);
        $data = $request->validate(['verification_code' => 'required|digits:6']);

        $error = DB::transaction(function () use ($request, $session, $data) {
            $challenge = PosSessionOpeningCode::where('user_id', $request->user()->id)
                ->where('caisse_id', $session->caisse_id)
                ->lockForUpdate()
                ->first();

            if (! $challenge || ! $challenge->consumed_at) {
                return 'Code d’ouverture introuvable pour cette session.';
            }

            if ($challenge->attempts >= 5) {
                return 'Trop de tentatives. Réessayez lors d’une nouvelle ouverture de caisse.';
            }

            if (! Hash::check($data['verification_code'], $challenge->code_hash)) {
                $challenge->increment('attempts');

                return 'Code incorrect. Vérifiez le code reçu par mail.';
            }

            $challenge->update(['attempts' => 0]);

            return null;
        });

        if ($error) {
            return response()->json(['message' => $error], 422);
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

    private function sendOpeningCode(Request $request, \App\Models\Caisse $caisse, bool $enforceCooldown = true): JsonResponse
    {
        $user = $request->user();
        $challenge = PosSessionOpeningCode::where('user_id', $user->id)
            ->where('caisse_id', $caisse->id)
            ->first();

        if ($enforceCooldown && $challenge?->last_sent_at?->gt(now()->subMinute())) {
            return response()->json(['message' => 'Attendez une minute avant de demander un nouveau code.'], 429);
        }

        $plainCode = (string) random_int(100000, 999999);
        $challenge = PosSessionOpeningCode::updateOrCreate(
            ['user_id' => $user->id, 'caisse_id' => $caisse->id],
            [
                'code_hash'  => Hash::make($plainCode),
                'attempts'   => 0,
                'expires_at' => now()->addMinutes(10),
                'last_sent_at' => now(),
                'consumed_at' => null,
            ]
        );

        try {
            $user->notify(new PosSessionOpeningCodeNotification($plainCode, $caisse->name));
        } catch (Throwable $e) {
            $challenge->update(['consumed_at' => now()]);
            $this->logError($e, 'Erreur lors de l\'envoi du code d\'ouverture de caisse', [
                'user_id' => $user->id,
                'caisse_id' => $caisse->id,
            ]);

            return response()->json(['message' => 'Le mail contenant le code n\'a pas pu être envoyé. Réessayez plus tard.'], 500);
        }

        return response()->json([
            'requires_code' => true,
            'message' => 'Un code à usage unique a été envoyé à votre adresse mail. Il expire dans 10 minutes.',
        ]);
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
