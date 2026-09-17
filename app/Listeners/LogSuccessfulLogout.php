<?php

namespace App\Listeners;

use App\Models\ActivityLog;
use Illuminate\Auth\Events\Logout;

class LogSuccessfulLogout
{
    public function handle(Logout $event): void
    {
        if (! $event->user) {
            return;
        }

        ActivityLog::log('logout', sprintf('Déconnexion de "%s".', $event->user->name), $event->user);
    }
}
