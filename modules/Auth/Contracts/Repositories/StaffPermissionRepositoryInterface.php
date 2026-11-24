<?php

namespace Modules\Auth\Contracts\Repositories;

use App\Models\User;
use Modules\Core\Enums\PermissionEnum;
use Modules\Core\Enums\SectionEnum;

interface StaffPermissionRepositoryInterface
{
    public function has(User $user, SectionEnum $section, PermissionEnum $permission): bool;
}
