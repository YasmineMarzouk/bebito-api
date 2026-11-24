<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class EnsureActiveAccount
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next, string $guard): Response
    {
        $user = $request->user($guard);

        if (! $user instanceof User || $user->is_blocked) {
            abort(Response::HTTP_FORBIDDEN);
        }

        return $next($request);
    }
}
