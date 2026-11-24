<?php

namespace Modules\Auth\UseCases;

use App\Models\User;
use Modules\Auth\Contracts\Repositories\StaffPermissionRepositoryInterface;
use Modules\Core\Enums\PermissionEnum;
use Modules\Core\Enums\SectionEnum;

final class AuthorizeStaffPermissionUseCase
{
    public function __construct(private readonly StaffPermissionRepositoryInterface $permissions) {}

    public function execute(User $user, SectionEnum $section, PermissionEnum $permission): bool
    {
        return $this->permissions->has($user, $section, $permission);
    }
}
