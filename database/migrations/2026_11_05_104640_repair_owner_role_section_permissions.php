<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Modules\Core\Enums\PermissionEnum;
use Modules\Core\Enums\SectionEnum;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $timestamp = now();
        $permissions = array_map(fn (PermissionEnum $permission): array => [
            'title' => $permission->value,
            'created_at' => $timestamp,
            'updated_at' => $timestamp,
        ], PermissionEnum::cases());
        $sections = array_map(fn (SectionEnum $section): array => [
            'title' => $section->value,
            'created_at' => $timestamp,
            'updated_at' => $timestamp,
        ], SectionEnum::cases());

        DB::table('permissions')->upsert($permissions, ['title'], ['updated_at']);
        DB::table('sections')->upsert($sections, ['title'], ['updated_at']);

        $ownerRoleId = DB::table('roles')->where('name', 'owner')->value('id');
        if ($ownerRoleId === null) {
            return;
        }

        $permissionIds = DB::table('permissions')->pluck('id', 'title');
        $sectionIds = DB::table('sections')->pluck('id', 'title');
        $roleSections = [];
        $sectionPermissions = [];
        $roleSectionPermissions = [];

        foreach (SectionEnum::cases() as $section) {
            $sectionId = $sectionIds[$section->value] ?? null;
            if ($sectionId === null) {
                continue;
            }

            $roleSections[] = [
                'role_id' => $ownerRoleId,
                'section_id' => $sectionId,
                'created_at' => $timestamp,
                'updated_at' => $timestamp,
            ];

            foreach (PermissionEnum::cases() as $permission) {
                $permissionId = $permissionIds[$permission->value] ?? null;
                if ($permissionId === null) {
                    continue;
                }

                $sectionPermissions[] = [
                    'section_id' => $sectionId,
                    'permission_id' => $permissionId,
                    'created_at' => $timestamp,
                    'updated_at' => $timestamp,
                ];
                $roleSectionPermissions[] = [
                    'role_id' => $ownerRoleId,
                    'section_id' => $sectionId,
                    'permission_id' => $permissionId,
                    'created_at' => $timestamp,
                    'updated_at' => $timestamp,
                ];
            }
        }

        DB::table('role_section')->upsert($roleSections, ['role_id', 'section_id'], ['updated_at']);
        DB::table('section_permission')->upsert($sectionPermissions, ['section_id', 'permission_id'], ['updated_at']);
        DB::table('role_section_permission')->upsert($roleSectionPermissions, ['role_id', 'section_id', 'permission_id'], ['updated_at']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // This data-repair migration deliberately preserves pre-existing role grants.
    }
};
