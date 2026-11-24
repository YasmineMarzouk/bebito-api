<?php

namespace Tests\Feature\Catalog;

use App\Models\User;
use Database\Seeders\StaffAccessSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Testing\TestResponse;
use Modules\Catalog\Enums\ProductGenderEnum;
use Modules\Catalog\Enums\ProductStatusEnum;
use Modules\Catalog\Models\Category;
use Modules\Catalog\Models\Product;
use Modules\Catalog\Models\ProductVariant;
use Modules\Core\Contracts\Repositories\AssetsRepositoryInterface;
use Modules\Core\Enums\PermissionEnum;
use Modules\Core\Enums\SectionEnum;
use Modules\Core\Enums\UserTypeEnum;
use Modules\Core\Models\Permission;
use Modules\Core\Models\Role;
use Modules\Core\Models\Section;
use Modules\Inventory\Enums\InventoryMovementReasonEnum;
use Modules\Inventory\Models\InventoryMovement;
use Modules\Orders\Enums\OrderStatusEnum;
use Modules\Orders\Models\Order;
use Tests\TestCase;

class ProductCatalogInventoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_staff_creation_derives_product_stock_sets_a_primary_image_and_records_initial_inventory(): void
    {
        Storage::fake('public');
        $staff = $this->staff();

        $response = $this->actingAs($staff, 'admin')->post('/api/v1/admin/products', [
            'payload' => json_encode($this->payload([
                $this->variant('0-3m', 'Blue', 5),
                $this->variant('3-6m', 'Pink', 2),
            ], 7)),
            'images' => [UploadedFile::fake()->create('primary.jpg', 100, 'image/jpeg')],
        ]);

        $response
            ->assertCreated()
            ->assertJsonPath('data.stockQuantity', 7)
            ->assertJsonPath('data.variants.0.isDefault', true)
            ->assertJsonPath('data.primaryImageId', 1);

        $product = Product::query()->sole();
        $this->assertSame(7, $product->stock_quantity);
        $this->assertSame(2, $product->variants()->count());
        $this->assertSame(1, $product->assets()->where('primary_image', true)->count());
        $this->assertDatabaseCount('inventory_movements', 2);
        $this->assertDatabaseHas('inventory_movements', [
            'reason' => InventoryMovementReasonEnum::InitialStock->value,
            'user_id' => $staff->id,
        ]);
    }

    public function test_product_creation_rejects_negative_stock_and_a_non_image_upload_without_writing_catalog_data(): void
    {
        Storage::fake('public');
        $staff = $this->staff();
        $payload = $this->payload([$this->variant('0-3m', 'Blue', -1)], -1);

        $this->actingAs($staff, 'admin')->post('/api/v1/admin/products', [
            'payload' => json_encode($payload),
            'images' => [UploadedFile::fake()->create('not-an-image.txt', 4, 'text/plain')],
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['stock_quantity', 'variants.0.stock_quantity', 'images.0']);

        $this->assertDatabaseCount('products', 0);
        $this->assertDatabaseCount('product_variants', 0);
        $this->assertDatabaseCount('assets', 0);
    }

    public function test_staff_can_list_products(): void
    {
        $staff = $this->staff();
        $product = $this->productWithVariants([1]);

        $this->actingAs($staff, 'admin')
            ->getJson('/api/v1/admin/products?per_page=1&page=1')
            ->assertOk()
            ->assertJsonPath('data.items.0.id', $product->id)
            ->assertJsonPath('data.currentPage', 1)
            ->assertJsonPath('data.lastPage', 1)
            ->assertJsonPath('data.perPage', 1)
            ->assertJsonPath('data.total', 1);
    }

    public function test_staff_can_search_products_and_update_a_product_status(): void
    {
        $staff = $this->staff();
        $matchingProduct = $this->productWithVariants([1]);
        $matchingProduct->translations()->update(['name' => 'Moonlight bodysuit']);
        $this->productWithVariants([1]);

        $this->actingAs($staff, 'admin')
            ->getJson('/api/v1/admin/products?search=Moonlight&per_page=1')
            ->assertOk()
            ->assertJsonCount(1, 'data.items')
            ->assertJsonPath('data.items.0.id', $matchingProduct->id)
            ->assertJsonPath('data.perPage', 1);

        $this->actingAs($staff, 'admin')
            ->patchJson('/api/v1/admin/products/'.$matchingProduct->id.'/status', ['status' => ProductStatusEnum::Draft->value])
            ->assertOk()
            ->assertJsonPath('data.status', ProductStatusEnum::Draft->value);

        $this->assertDatabaseHas('products', ['id' => $matchingProduct->id, 'status' => ProductStatusEnum::Draft->value]);

        $this->actingAs($staff, 'admin')
            ->patchJson('/api/v1/admin/products/'.$matchingProduct->id.'/status', ['status' => 99])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['status']);
    }

    public function test_product_update_preserves_variant_identity_derives_stock_and_records_adjustments(): void
    {
        $staff = $this->staff();
        $product = $this->productWithVariants([5, 2]);
        $variants = $product->variants()->orderBy('id')->get();

        $this->actingAs($staff, 'admin')
            ->putJson('/api/v1/admin/products/'.$product->id, $this->payload([
                $this->variant('0-3m', 'Blue', 2, $variants[0]->id),
                $this->variant('3-6m', 'Pink', 5, $variants[1]->id),
            ], 999))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['variants']);

        $this->actingAs($staff, 'admin')
            ->putJson('/api/v1/admin/products/'.$product->id, $this->payload([
                $this->variant('0-3m', 'Blue', 2, $variants[0]->id),
                $this->variant('3-6m', 'Pink', 5, $variants[1]->id),
            ], 7))
            ->assertOk()
            ->assertJsonPath('data.stockQuantity', 7);

        $this->assertDatabaseHas('product_variants', ['id' => $variants[0]->id, 'is_active' => true]);
        $this->assertDatabaseHas('product_variants', ['id' => $variants[1]->id, 'is_active' => true]);
        $this->assertDatabaseHas('inventory_items', ['product_variant_id' => $variants[0]->id, 'quantity' => 2]);
        $this->assertDatabaseHas('inventory_items', ['product_variant_id' => $variants[1]->id, 'quantity' => 5]);
        $this->assertDatabaseHas('products', ['id' => $product->id, 'stock_quantity' => 7]);
        $this->assertSame(2, InventoryMovement::query()->where('reason', InventoryMovementReasonEnum::ManualAdjustment->value)->count());
        $this->assertDatabaseHas('inventory_movements', ['reason' => InventoryMovementReasonEnum::ManualAdjustment->value, 'user_id' => $staff->id, 'quantity_change' => -3]);
        $this->assertDatabaseHas('inventory_movements', ['reason' => InventoryMovementReasonEnum::ManualAdjustment->value, 'user_id' => $staff->id, 'quantity_change' => 3]);
    }

    public function test_staff_can_assign_multiple_categories_to_a_product(): void
    {
        Storage::fake('public');
        $staff = $this->staff();
        $firstCategory = Category::query()->create(['is_active' => true]);
        $secondCategory = Category::query()->create(['is_active' => true]);
        $thirdCategory = Category::query()->create(['is_active' => true]);

        $response = $this->actingAs($staff, 'admin')->post('/api/v1/admin/products', [
            'payload' => json_encode([
                ...$this->payload([$this->variant('0-3m', 'Blue', 1)], 1),
                'category_ids' => [$firstCategory->id, $secondCategory->id],
            ]),
            'images' => [UploadedFile::fake()->create('product.jpg', 100, 'image/jpeg')],
        ]);

        $response->assertCreated();
        $product = Product::query()->sole();
        $this->assertEqualsCanonicalizing(
            [$firstCategory->id, $secondCategory->id],
            $product->categories()->pluck('categories.id')->all(),
        );

        $variant = $product->variants()->sole();
        $this->actingAs($staff, 'admin')
            ->putJson('/api/v1/admin/products/'.$product->id, [
                ...$this->payload([$this->variant('0-3m', 'Blue', 1, $variant->id)], 1),
                'category_ids' => [$secondCategory->id, $thirdCategory->id],
            ])
            ->assertOk();

        $this->assertEqualsCanonicalizing(
            [$secondCategory->id, $thirdCategory->id],
            $product->fresh()->categories()->pluck('categories.id')->all(),
        );
    }

    public function test_removing_a_variant_retires_it_and_prevents_customer_checkout_without_erasing_history(): void
    {
        $staff = $this->staff();
        $product = $this->productWithVariants([2, 5]);
        $variants = $product->variants()->orderBy('id')->get();
        $retiredVariant = $variants[0];
        $activeVariant = $variants[1];
        $priorMovementCount = InventoryMovement::query()->count();

        $this->actingAs($staff, 'admin')
            ->putJson('/api/v1/admin/products/'.$product->id, $this->payload([
                $this->variant('3-6m', 'Pink', 5, $activeVariant->id),
            ], 5))
            ->assertOk()
            ->assertJsonCount(1, 'data.variants')
            ->assertJsonPath('data.variants.0.id', $activeVariant->id);

        $this->assertDatabaseHas('product_variants', ['id' => $retiredVariant->id, 'is_active' => false]);
        $this->assertDatabaseHas('inventory_items', ['product_variant_id' => $retiredVariant->id, 'quantity' => 0]);
        $this->assertDatabaseHas('products', ['id' => $product->id, 'stock_quantity' => 5]);
        $this->assertSame($priorMovementCount + 1, InventoryMovement::query()->count());
        $this->assertDatabaseHas('inventory_movements', [
            'inventory_item_id' => $retiredVariant->inventoryItem->id,
            'reason' => InventoryMovementReasonEnum::ManualAdjustment->value,
            'quantity_change' => -2,
        ]);

        $this->checkout($retiredVariant)->assertUnprocessable();
    }

    public function test_product_update_rejects_a_variant_owned_by_another_product(): void
    {
        $staff = $this->staff();
        $product = $this->productWithVariants([2]);
        $foreignVariant = $this->productWithVariants([1])->variants()->sole();

        $this->actingAs($staff, 'admin')
            ->putJson('/api/v1/admin/products/'.$product->id, $this->payload([
                $this->variant('0-3m', 'Blue', 2, $foreignVariant->id),
            ], 2))
            ->assertUnprocessable()
            ->assertJsonPath('message', 'One or more product variants do not belong to this product.');
    }

    public function test_product_delete_soft_deletes_the_catalog_entry_and_preserves_order_and_inventory_history(): void
    {
        $staff = $this->staff();
        $product = $this->productWithVariants([3]);
        $variant = $product->variants()->sole();
        $order = Order::query()->create([
            'number' => 'BB-HISTORY-1',
            'customer_name' => 'Catalog Test',
            'customer_email' => 'catalog@example.test',
            'status' => OrderStatusEnum::PENDING->value,
            'payment_method_code' => 'cash_on_delivery',
            'total_amount' => 12000,
        ]);
        $order->items()->create([
            'product_variant_id' => $variant->id,
            'product_name' => 'Test product',
            'sku' => $variant->sku,
            'quantity' => 1,
            'unit_price_amount' => 12000,
            'total_amount' => 12000,
        ]);
        $movementCount = InventoryMovement::query()->count();

        $this->actingAs($staff, 'admin')
            ->deleteJson('/api/v1/admin/products/'.$product->id)
            ->assertOk();

        $this->assertNull(Product::query()->find($product->id));
        $this->assertNotNull(Product::query()->withTrashed()->find($product->id)?->deleted_at);
        $this->assertDatabaseHas('product_variants', ['id' => $variant->id]);
        $this->assertDatabaseHas('order_items', ['order_id' => $order->id, 'product_variant_id' => $variant->id]);
        $this->assertSame($movementCount, InventoryMovement::query()->count());
        $this->getJson('/api/v1/shop/products/'.$product->id)->assertNotFound();
    }

    public function test_staff_can_manage_categories_and_collections_while_a_customer_cannot_use_admin_catalog_routes(): void
    {
        $staff = $this->staff();
        $categoryPayload = [
            'is_active' => true,
            'translations' => [
                ['locale_code' => 'fr', 'name' => 'Bodies', 'slug' => 'bodies', 'description' => null],
            ],
        ];
        $collectionPayload = [
            'is_active' => true,
            'is_featured' => false,
            'sort_order' => 0,
            'translations' => [
                ['locale_code' => 'fr', 'name' => 'Nouvelle collection', 'slug' => 'nouvelle-collection', 'description' => null],
            ],
        ];

        $category = $this->actingAs($staff, 'admin')
            ->postJson('/api/v1/admin/categories', $categoryPayload)
            ->assertCreated()
            ->json('data');
        $collection = $this->actingAs($staff, 'admin')
            ->postJson('/api/v1/admin/collections', $collectionPayload)
            ->assertCreated()
            ->json('data');

        $this->actingAs($staff, 'admin')
            ->putJson('/api/v1/admin/categories/'.$category['id'], [...$categoryPayload, 'is_active' => false])
            ->assertOk()
            ->assertJsonPath('data.isActive', false);
        $this->actingAs($staff, 'admin')
            ->putJson('/api/v1/admin/collections/'.$collection['id'], [...$collectionPayload, 'is_featured' => true, 'sort_order' => 2])
            ->assertOk()
            ->assertJsonPath('data.isFeatured', true)
            ->assertJsonPath('data.sortOrder', 2);

        $this->actingAs($staff, 'admin')->deleteJson('/api/v1/admin/categories/'.$category['id'])->assertOk();
        $this->actingAs($staff, 'admin')->deleteJson('/api/v1/admin/collections/'.$collection['id'])->assertOk();

        $customer = User::factory()->create(['user_type' => UserTypeEnum::Customer->value]);
        $this->app['auth']->forgetGuards();
        $this->actingAs($customer, 'customer')->getJson('/api/v1/admin/categories')->assertUnauthorized();
        $this->actingAs($customer, 'customer')->postJson('/api/v1/admin/collections', [])->assertUnauthorized();
    }

    public function test_catalog_management_accepts_only_one_french_translation(): void
    {
        Storage::fake('public');
        $staff = $this->staff();
        $productPayload = $this->payload([$this->variant('0-3m', 'Blue', 1)], 1);
        $productPayload['translations'][] = [
            'locale_code' => 'en',
            'name' => 'Test product',
            'slug' => 'test-product-en',
            'short_description' => null,
            'description' => null,
        ];

        $this->actingAs($staff, 'admin')->post('/api/v1/admin/products', [
            'payload' => json_encode($productPayload),
            'images' => [UploadedFile::fake()->image('product.jpg')],
        ])->assertUnprocessable()->assertJsonValidationErrors(['translations']);

        $this->actingAs($staff, 'admin')->postJson('/api/v1/admin/categories', [
            'translations' => [['locale_code' => 'en', 'name' => 'Bodies', 'slug' => 'bodies']],
        ])->assertUnprocessable()->assertJsonValidationErrors(['translations.0.locale_code']);

        $this->actingAs($staff, 'admin')->postJson('/api/v1/admin/collections', [
            'translations' => [['locale_code' => 'en', 'name' => 'Collection', 'slug' => 'collection']],
        ])->assertUnprocessable()->assertJsonValidationErrors(['translations.0.locale_code']);

        $this->actingAs($staff, 'admin')->postJson('/api/v1/admin/packs', [
            'translations' => [['locale_code' => 'en', 'name' => 'Pack', 'slug' => 'pack']],
        ])->assertUnprocessable()->assertJsonValidationErrors(['translations.0.locale_code']);
    }

    public function test_staff_with_read_only_product_permission_cannot_mutate_products(): void
    {
        $product = $this->productWithVariants([1]);
        $staff = $this->staffWithProductPermission(PermissionEnum::Read);

        $this->actingAs($staff, 'admin')->getJson('/api/v1/admin/products')->assertOk();
        $this->actingAs($staff, 'admin')->postJson('/api/v1/admin/products', [])->assertForbidden();
        $this->actingAs($staff, 'admin')->putJson('/api/v1/admin/products/'.$product->id, [])->assertForbidden();
        $this->actingAs($staff, 'admin')->deleteJson('/api/v1/admin/products/'.$product->id)->assertForbidden();
    }

    public function test_product_write_and_execute_permissions_are_enforced_independently(): void
    {
        $product = $this->productWithVariants([1]);
        $writer = $this->staffWithProductPermission(PermissionEnum::Write);

        $this->actingAs($writer, 'admin')->postJson('/api/v1/admin/products', [])->assertUnprocessable();
        $this->actingAs($writer, 'admin')->putJson('/api/v1/admin/products/'.$product->id, [])->assertUnprocessable();
        $this->actingAs($writer, 'admin')->deleteJson('/api/v1/admin/products/'.$product->id)->assertForbidden();

        $executor = $this->staffWithProductPermission(PermissionEnum::Execute);
        $this->actingAs($executor, 'admin')->deleteJson('/api/v1/admin/products/'.$product->id)->assertOk();
    }

    public function test_primary_image_endpoint_rejects_an_asset_from_another_product_without_changing_the_current_primary_image(): void
    {
        $staff = $this->staff();
        $assets = $this->app->make(AssetsRepositoryInterface::class);
        $firstProduct = $this->productWithVariants([1]);
        $secondProduct = $this->productWithVariants([1]);
        $firstAsset = $assets->create($this->assetAttributes($firstProduct->id, 'first.jpg', true));
        $foreignAsset = $assets->create($this->assetAttributes($secondProduct->id, 'second.jpg', true));

        $this->actingAs($staff, 'admin')
            ->putJson('/api/v1/admin/products/'.$firstProduct->id.'/primary-image', ['primary_image_id' => $foreignAsset->id])
            ->assertNotFound();

        $this->assertTrue((bool) $firstAsset->fresh()->primary_image);
        $this->assertTrue((bool) $foreignAsset->fresh()->primary_image);
    }

    /** @param array<int, int> $quantities */
    private function productWithVariants(array $quantities): Product
    {
        $product = Product::query()->create([
            'status' => ProductStatusEnum::Published->value,
            'sku' => 'BEB-'.str_pad((string) (Product::query()->count() + 1), 6, '0', STR_PAD_LEFT),
            'price_amount' => 12000,
            'stock_quantity' => array_sum($quantities),
            'low_stock_threshold' => 2,
        ]);
        $product->translations()->create(['locale_code' => 'fr', 'name' => 'Test product', 'slug' => 'test-product-'.($product->id)]);

        foreach ($quantities as $index => $quantity) {
            $variant = $product->allVariants()->create([
                'sku' => $product->sku.'-'.str_pad((string) ($index + 1), 2, '0', STR_PAD_LEFT),
                'attributes' => ['size' => ['0-3m', '3-6m'][$index] ?? '6-12m', 'color' => $index === 0 ? 'Blue' : 'Pink'],
                'is_default' => $index === 0,
                'is_active' => true,
            ]);
            $inventory = $variant->inventoryItem()->create(['quantity' => $quantity, 'low_stock_threshold' => 2]);
            InventoryMovement::query()->create([
                'inventory_item_id' => $inventory->id,
                'quantity_change' => $quantity,
                'reason' => InventoryMovementReasonEnum::InitialStock->value,
            ]);
        }

        return $product;
    }

    /** @return array<string, mixed> */
    private function payload(array $variants, int $stockQuantity): array
    {
        return [
            'status' => ProductStatusEnum::Published->value,
            'gender' => ProductGenderEnum::Unisex->value,
            'translations' => [
                ['locale_code' => 'fr', 'name' => 'Test product', 'slug' => 'test-product', 'short_description' => null, 'description' => null],
            ],
            'price_amount' => 12000,
            'compare_at_price_amount' => 15000,
            'discount_percentage' => 20,
            'stock_quantity' => $stockQuantity,
            'low_stock_threshold' => 2,
            'category_ids' => [],
            'collection_ids' => [],
            'tags' => [],
            'variants' => $variants,
        ];
    }

    /** @return array<string, mixed> */
    private function variant(string $size, string $color, int $stock, ?int $id = null): array
    {
        return array_filter([
            'id' => $id,
            'attributes' => ['size' => $size, 'color' => $color],
            'stock_quantity' => $stock,
            'low_stock_threshold' => 2,
        ], fn (mixed $value): bool => $value !== null);
    }

    private function staff(): User
    {
        $owner = Role::query()->create(['name' => 'owner', 'title' => 'Owner']);
        $this->seed(StaffAccessSeeder::class);
        $staff = User::factory()->create(['user_type' => UserTypeEnum::Staff->value]);
        $staff->roles()->attach($owner);

        return $staff;
    }

    private function staffWithProductPermission(PermissionEnum $permission): User
    {
        $role = Role::query()->create(['name' => 'catalog-'.$permission->value, 'title' => 'Catalog '.$permission->value]);
        $this->seed(StaffAccessSeeder::class);
        $section = Section::query()->where('title', SectionEnum::Products->value)->sole();
        $permissionModel = Permission::query()->where('title', $permission->value)->sole();
        $role->sections()->attach($section);
        DB::table('role_section_permission')->insert([
            'role_id' => $role->id,
            'section_id' => $section->id,
            'permission_id' => $permissionModel->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $staff = User::factory()->create(['user_type' => UserTypeEnum::Staff->value]);
        $staff->roles()->attach($role);

        return $staff;
    }

    /** @return array<string, int|string|bool> */
    private function assetAttributes(int $productId, string $path, bool $primaryImage): array
    {
        return [
            'resource_type' => 'product',
            'resource_id' => $productId,
            'disk' => 'public',
            'path' => 'products/'.$path,
            'original_name' => $path,
            'mime_type' => 'image/jpeg',
            'size' => 1024,
            'sort_order' => 0,
            'primary_image' => $primaryImage,
        ];
    }

    private function checkout(ProductVariant $variant): TestResponse
    {
        return $this->postJson('/api/v1/store/checkout', [
            'first_name' => 'Marie',
            'last_name' => 'Dupont',
            'email' => 'marie@example.test',
            'phone' => '+21620000000',
            'address' => '1 Rue des Fleurs',
            'city' => 'Tunis',
            'items' => [['variant_id' => $variant->id, 'quantity' => 1]],
        ], ['Idempotency-Key' => 'd6f1a6db-1e23-41d7-8c34-5f0b6a8ccfcc']);
    }
}
