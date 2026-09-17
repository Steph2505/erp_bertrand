<?php

namespace App\Helpers;

use App\Models\Setting;
use Carbon\Carbon;

class PeriodLock
{
    /**
     * Date jusqu'à laquelle la période comptable est verrouillée (incluse),
     * ou null si aucune clôture n'a été définie.
     */
    public static function lockedUntil(): ?string
    {
        return Setting::get('accounting_locked_until') ?: null;
    }

    /**
     * Une opération datée de $date touche-t-elle une période déjà clôturée ?
     */
    public static function isLocked(string $date): bool
    {
        $lockedUntil = static::lockedUntil();

        if (! $lockedUntil) {
            return false;
        }

        return Carbon::parse($date)->lte(Carbon::parse($lockedUntil));
    }
}
