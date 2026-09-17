<?php

namespace App\Http\Controllers;

use App\Helpers\FormatHelper;
use App\Helpers\PeriodLock;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Log;
use Throwable;

abstract class Controller
{
    /**
     * Journalise une exception avec le contexte utile au diagnostic.
     */
    protected function logError(Throwable $e, string $context, array $extra = []): void
    {
        Log::error($context, array_merge([
            'exception' => $e->getMessage(),
            'file'      => $e->getFile(),
            'line'      => $e->getLine(),
            'user_id'   => auth()->id(),
        ], $extra));
    }

    /**
     * Bloque la création/modification/suppression d'une pièce datée d'une
     * période comptable déjà clôturée. Retourne une redirection si bloqué,
     * null sinon — l'appelant fait `if ($blocked = $this->blockIfPeriodLocked($date)) return $blocked;`.
     */
    protected function blockIfPeriodLocked(?string $date): ?RedirectResponse
    {
        if (! $date || ! PeriodLock::isLocked($date)) {
            return null;
        }

        return back()->withInput()->with('error', sprintf(
            'Impossible : cette opération est datée du %s, dans une période comptable clôturée jusqu\'au %s.',
            FormatHelper::date($date),
            FormatHelper::date(PeriodLock::lockedUntil())
        ));
    }
}
