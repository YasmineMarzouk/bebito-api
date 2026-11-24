<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Modules\Auth\Enums\AuthenticationContextEnum;
use Symfony\Component\HttpFoundation\Response;

final class ResolveAuthenticationContext
{
    public const string Attribute = 'authentication_context';

    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $context = $this->contextFor($request);

        if ($context === null) {
            return $next($request);
        }

        $request->attributes->set(self::Attribute, $context);
        config([
            'session.cookie' => config("auth-context.{$context->value}.session_cookie"),
            'session.table' => config("auth-context.{$context->value}.session_table"),
        ]);

        $origin = $request->headers->get('Origin');
        $origins = config("auth-context.{$context->value}.origins", []);

        if ($origin !== null && ! in_array($origin, $origins, true)) {
            abort(Response::HTTP_FORBIDDEN);
        }

        return $next($request);
    }

    private function contextFor(Request $request): ?AuthenticationContextEnum
    {
        if ($request->is('api/v1/admin/*')) {
            return AuthenticationContextEnum::Admin;
        }

        if ($request->is('api/v1/customer/*') || $request->is('api/v1/store/*')) {
            return AuthenticationContextEnum::Customer;
        }

        return null;
    }
}
