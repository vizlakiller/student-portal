<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * New student accounts start with their student number as the password.
 * Until they choose their own, every page sends them to "My profile".
 */
class EnsurePasswordIsChanged
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user?->must_change_password && ! $request->routeIs('profile.*', 'logout')) {
            return redirect()->route('profile.edit');   // the page explains why
        }

        return $next($request);
    }
}
