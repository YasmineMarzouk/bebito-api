<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Modules\Auth\UseCases\AuthorizeStaffPermissionUseCase;
use Modules\Core\Enums\PermissionEnum;
use Modules\Core\Enums\SectionEnum;
use Modules\Core\Enums\UserTypeEnum;
use Symfony\Component\HttpFoundation\Response;

final class EnsureStaffPermission
{
    public function __construct(private readonly AuthorizeStaffPermissionUseCase $useCase) {}

    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next, string $section, string $permission): Response
    {
        $user = $request->user('admin');

        try {
            $sectionEnum = SectionEnum::from($section);
        } catch (\ValueError) {
            abort(Response::HTTP_FORBIDDEN);
        }

        $permissionEnum = PermissionEnum::fromRouteParameter($permission);

        if ($permissionEnum === null) {
            abort(Response::HTTP_FORBIDDEN);
        }

        if (! $user instanceof User
            || $user->user_type !== UserTypeEnum::Staff
            || ! $this->useCase->execute($user, $sectionEnum, $permissionEnum)) {
            abort(Response::HTTP_FORBIDDEN);
        }

        return $next($request);
    }
}
