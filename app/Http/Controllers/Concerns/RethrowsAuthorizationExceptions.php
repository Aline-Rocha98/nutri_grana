<?php

namespace App\Http\Controllers\Concerns;

use Illuminate\Auth\Access\AuthorizationException;
use Throwable;

trait RethrowsAuthorizationExceptions
{
    protected function rethrowIfAuthorization(Throwable $e): void
    {
        if ($e instanceof AuthorizationException) {
            throw $e;
        }
    }
}
