<?php

namespace App\Http\Middleware;

use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Cookie\CookieValuePrefix;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Modules\Auth\Enums\AuthenticationContextEnum;
use Symfony\Component\HttpFoundation\Cookie;

final class ContextCsrfToken extends PreventRequestForgery
{
    protected function getTokenFromRequest($request)
    {
        $token = $request->input('_token') ?: $request->header('X-CSRF-TOKEN');

        if (! $token && $header = $request->header('X-XSRF-TOKEN')) {
            try {
                $token = CookieValuePrefix::remove(
                    $this->encrypter->decrypt($header, EncryptCookies::serialized($this->cookieName($request))),
                );
            } catch (DecryptException) {
                $token = '';
            }
        }

        return $token;
    }

    protected function newCookie($request, $config): Cookie
    {
        return new Cookie(
            $this->cookieName($request),
            $request->session()->token(),
            $this->availableAt(60 * $config['lifetime']),
            $config['path'],
            $config['domain'],
            $config['secure'],
            false,
            false,
            $config['same_site'] ?? null,
            $config['partitioned'] ?? false,
        );
    }

    private function cookieName($request): string
    {
        $context = $request->attributes->get(ResolveAuthenticationContext::Attribute);

        if (! $context instanceof AuthenticationContextEnum) {
            return 'XSRF-TOKEN';
        }

        return (string) config("auth-context.{$context->value}.xsrf_cookie");
    }
}
