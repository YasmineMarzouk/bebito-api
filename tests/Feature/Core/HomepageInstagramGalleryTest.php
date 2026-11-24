<?php

namespace Tests\Feature\Core;

use App\Models\User;
use Database\Seeders\StaffAccessSeeder;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Modules\Core\Contracts\Repositories\SettingsRepositoryInterface;
use Modules\Core\Enums\UserTypeEnum;
use Modules\Core\Models\Asset;
use Modules\Core\Models\Role;
use Tests\TestCase;

class HomepageInstagramGalleryTest extends TestCase
{
    use RefreshDatabase;

    public function test_staff_can_upload_and_replace_individual_ordered_homepage_instagram_images(): void
    {
        Storage::fake('public');
        $staff = $this->ownerStaff();
        $this->app->make(SettingsRepositoryInterface::class)->save(['social.instagram' => 'https://www.instagram.com/bebito.baby.store/']);

        $this->actingAs($staff, 'admin')
            ->post('/api/v1/admin/settings/homepage-instagram-images', [
                'images' => [
                    0 => $this->image('first.png'),
                    3 => $this->image('fourth.png'),
                ],
            ])
            ->assertOk()
            ->assertJsonPath('data.instagramUrl', 'https://www.instagram.com/bebito.baby.store/')
            ->assertJsonCount(2, 'data.images')
            ->assertJsonPath('data.images.0.position', 0)
            ->assertJsonPath('data.images.1.position', 3);

        $firstAsset = Asset::query()->where('resource_type', 'homepage_instagram_gallery')->where('sort_order', 0)->sole();
        Storage::disk('public')->assertExists($firstAsset->path);

        $this->actingAs($staff, 'admin')
            ->post('/api/v1/admin/settings/homepage-instagram-images', ['images' => [0 => $this->image('replacement.png')]])
            ->assertOk()
            ->assertJsonCount(2, 'data.images')
            ->assertJsonPath('data.images.0.position', 0)
            ->assertJsonPath('data.images.1.position', 3);

        Storage::disk('public')->assertMissing($firstAsset->path);
        $this->assertSame(2, Asset::query()->where('resource_type', 'homepage_instagram_gallery')->count());
        $this->assertSame(1, Asset::query()->where('resource_type', 'homepage_instagram_gallery')->where('sort_order', 0)->count());
    }

    public function test_asset_sort_order_is_unique_within_its_resource(): void
    {
        Asset::query()->create([
            'resource_type' => 'homepage_instagram_gallery',
            'resource_id' => 1,
            'disk' => 'public',
            'path' => 'homepage/instagram/first.jpg',
            'sort_order' => 0,
            'primary_image' => true,
        ]);

        $this->expectException(UniqueConstraintViolationException::class);

        Asset::query()->create([
            'resource_type' => 'homepage_instagram_gallery',
            'resource_id' => 1,
            'disk' => 'public',
            'path' => 'homepage/instagram/replacement.jpg',
            'sort_order' => 0,
            'primary_image' => false,
        ]);
    }

    public function test_homepage_instagram_gallery_rejects_non_image_uploads(): void
    {
        $this->actingAs($this->ownerStaff(), 'admin')
            ->post('/api/v1/admin/settings/homepage-instagram-images', ['images' => [UploadedFile::fake()->create('not-an-image.txt', 4, 'text/plain')]])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['images.0']);
    }

    private function ownerStaff(): User
    {
        $owner = Role::query()->create(['name' => 'owner', 'title' => 'Owner']);
        $this->seed(StaffAccessSeeder::class);
        $staff = User::factory()->create(['user_type' => UserTypeEnum::Staff->value]);
        $staff->roles()->attach($owner);

        return $staff;
    }

    private function image(string $name): UploadedFile
    {
        return UploadedFile::fake()->createWithContent(
            $name,
            base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVQIHWP4z8DwHwAFgAI/ScL5nAAAAABJRU5ErkJggg==', true) ?: '',
        );
    }
}
