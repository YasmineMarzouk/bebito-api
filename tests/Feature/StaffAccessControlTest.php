<?php

namespace Tests\Feature;

use Database\Seeders\StaffAccessSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Core\Enums\PermissionEnum;
use Tests\TestCase;

class StaffAccessControlTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_staff_access_catalog_is_seeded_with_sections_and_permissions(): void
    {
        $this->seed(StaffAccessSeeder::class);

        $this->assertDatabaseCount('permissions', count(PermissionEnum::cases()));
        $this->assertDatabaseCount('sections', 12);
        $this->assertDatabaseHas('sections', ['title' => 'analytics']);
        $this->assertDatabaseHas('sections', ['title' => 'settings']);
        $this->assertDatabaseCount('section_permission', 36);
    }
}
