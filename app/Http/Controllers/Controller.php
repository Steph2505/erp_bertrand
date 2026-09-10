<?php

namespace App\Http\Controllers;

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
}
