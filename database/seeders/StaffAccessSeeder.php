<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Modules\Core\Enums\PermissionEnum;
use Modules\Core\Enums\SectionEnum;
use Modules\Core\Enums\UserTypeEnum;
use Modules\Core\Models\Permission;
use Modules\Core\Models\Role;
use Modules\Core\Models\Section;

class StaffAccessSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('users')->whereIn('id', function ($query): void {
            $query->select('user_role.user_id')
                ->from('user_role')
                ->join('roles', 'roles.id', '=', 'user_role.role_id')
                ->where('roles.name', '!=', 'customer');
        })->update(['user_type' => UserTypeEnum::Staff->value]);

        $permissions = collect(PermissionEnum::cases())
            ->mapWithKeys(fn (PermissionEnum $permission): array => [$permission->value => Permission::query()->firstOrCreate(['title' => $permission->value])]);
        $sections = collect(SectionEnum::cases())
            ->map(fn (SectionEnum $section): Section => Section::query()->firstOrCreate(['title' => $section->value]));

        foreach ($sections as $section) {
            $section->permissions()->sync($permissions->pluck('id')->all());
        }

        $owner = Role::query()->where('name', 'owner')->first();
        if ($owner === null) {
            return;
        }

        $owner->sections()->sync($sections->pluck('id')->all());
        $timestamp = now();
        $rows = $sections->flatMap(fn (Section $section): array => $permissions->map(fn (Permission $permission): array => [
            'role_id' => $owner->id,
            'section_id' => $section->id,
            'permission_id' => $permission->id,
            'created_at' => $timestamp,
            'updated_at' => $timestamp,
        ])->all())->all();
        DB::table('role_section_permission')->upsert($rows, ['role_id', 'section_id', 'permission_id'], ['updated_at']);
    }
}
