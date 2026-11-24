<?php

namespace Modules\Auth\Repositories;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Modules\Auth\Contracts\Repositories\StaffPermissionRepositoryInterface;
use Modules\Core\Enums\PermissionEnum;
use Modules\Core\Enums\SectionEnum;

final class StaffPermissionRepository implements StaffPermissionRepositoryInterface
{
    public function has(User $user, SectionEnum $section, PermissionEnum $permission): bool
    {
        return DB::table('user_role')
            ->join('role_section', 'role_section.role_id', '=', 'user_role.role_id')
            ->join('sections', 'sections.id', '=', 'role_section.section_id')
            ->join('role_section_permission', function ($join): void {
                $join->on('role_section_permission.role_id', '=', 'role_section.role_id')
                    ->on('role_section_permission.section_id', '=', 'role_section.section_id');
            })
            ->join('permissions', 'permissions.id', '=', 'role_section_permission.permission_id')
            ->where('user_role.user_id', $user->id)
            ->where('sections.title', $section->value)
            ->where('permissions.title', $permission->value)
            ->exists();
    }
}
