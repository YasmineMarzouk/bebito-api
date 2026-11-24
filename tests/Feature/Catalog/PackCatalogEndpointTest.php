<?php

namespace Tests\Feature\Catalog;

use App\Models\User;
use Database\Seeders\StaffAccessSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Catalog\Enums\ProductStatusEnum;
use Modules\Core\Enums\UserTypeEnum;
use Modules\Core\Models\Role;
use Tests\Concerns\CreatesPackFixtures;
use Tests\TestCase;

class PackCatalogEndpointTest extends TestCase
{
    use CreatesPackFixtures;
    use RefreshDatabase;

    public function test_staff_can_search_packs_and_update_a_pack_status(): void
    {
        $staff = $this->staff();
        $first = $this->createSellableVariant('PACK-ENDPOINT-FIRST', 10, 10000);
        $first->product->translations()->create([
            'locale_code' => 'fr',
            'name' => 'First component',
            'slug' => 'first-component',
        ]);
        $second = $this->createSellableVariant('PACK-ENDPOINT-SECOND', 10, 5000);
        $second->product->translations()->create([
            'locale_code' => 'fr',
            'name' => 'Second component',
            'slug' => 'second-component',
        ]);
        $fixture = $this->createSellablePack('BPK-ENDPOINT', 12000, [
            ['variant' => $first, 'quantity' => 1],
            ['variant' => $second, 'quantity' => 1],
        ]);
        $pack = $fixture['pack'];
        $pack->translations()->update(['name' => 'Moonlight bundle']);
        $pack->update(['status' => ProductStatusEnum::Draft->value]);

        $this->actingAs($staff, 'admin')
            ->getJson('/api/v1/admin/packs?search=Moonlight&per_page=1')
            ->assertOk()
            ->assertJsonCount(1, 'data.items')
            ->assertJsonPath('data.items.0.id', $pack->id)
            ->assertJsonPath('data.items.0.components.0.productName', 'First component')
            ->assertJsonPath('data.perPage', 1);

        $response = $this->actingAs($staff, 'admin')
            ->patchJson('/api/v1/admin/packs/'.$pack->id.'/status', ['status' => ProductStatusEnum::Published->value])
            ->assertOk()
            ->assertJsonPath('data.status', ProductStatusEnum::Published->value);

        $this->assertNotNull($response->json('data.firstPublishedAt'));
        $this->assertDatabaseHas('packs', [
            'id' => $pack->id,
            'status' => ProductStatusEnum::Published->value,
        ]);

        $this->actingAs($staff, 'admin')
            ->patchJson('/api/v1/admin/packs/'.$pack->id.'/status', ['status' => ProductStatusEnum::Draft->value])
            ->assertOk()
            ->assertJsonPath('data.status', ProductStatusEnum::Draft->value);

        $this->assertNotNull($pack->fresh()->first_published_at);

        $this->actingAs($staff, 'admin')
            ->patchJson('/api/v1/admin/packs/'.$pack->id.'/status', ['status' => 99])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['status']);
    }

    private function staff(): User
    {
        $owner = Role::query()->create(['name' => 'owner', 'title' => 'Owner']);
        $this->seed(StaffAccessSeeder::class);
        $staff = User::factory()->create(['user_type' => UserTypeEnum::Staff->value]);
        $staff->roles()->attach($owner);

        return $staff;
    }
}
