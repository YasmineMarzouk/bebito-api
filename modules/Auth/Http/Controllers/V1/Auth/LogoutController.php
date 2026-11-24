<?php

namespace Modules\Auth\Http\Controllers\V1\Auth;

use App\Http\Middleware\ResolveAuthenticationContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Modules\Auth\Enums\AuthenticationContextEnum;
use Modules\Core\Http\Controllers\BaseController;

final class LogoutController extends BaseController
{
    public function __invoke(Request $request): JsonResponse
    {
        $context = $request->attributes->get(ResolveAuthenticationContext::Attribute);

        if (! $context instanceof AuthenticationContextEnum) {
            abort(500);
        }

        Auth::guard($context->guard())->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return $this->makeSuccess('Logged out.', null);
    }
}
