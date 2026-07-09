<?php

namespace App\Http\Controllers;

use Inertia\Inertia;

abstract class Controller
{
    /**
     * Flash a one-time toast notification for the next Inertia response.
     */
    protected function toast(string $message, string $type = 'success'): void
    {
        Inertia::flash('toast', ['type' => $type, 'message' => $message]);
    }
}
