<?php

namespace Modules\Auth\Http\Controllers\V1\Auth;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Modules\Core\Http\Controllers\BaseController;

final class LogoutController extends BaseController
{
    public function __invoke(Request $request): JsonResponse
    {
        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return $this->makeSuccess('Logged out.', null);
    }
}
