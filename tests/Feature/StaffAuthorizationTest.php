<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\StaffAccessSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Catalog\Models\Product;
use Modules\Catalog\Models\ProductVariant;
use Modules\Catalog\Enums\ProductStatusEnum;
use Modules\Core\Enums\UserTypeEnum;
use Modules\Core\Models\Asset;
use Modules\Core\Models\Role;
use Modules\Orders\Enums\OrderStatusEnum;
use Modules\Orders\Enums\AddressTypeEnum;
use Modules\Orders\Models\Order;
use Tests\TestCase;

class StaffAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_staff_without_an_assigned_permission_cannot_read_admin_catalog_data(): void
    {
        $staff = User::factory()->create(['user_type' => UserTypeEnum::Staff->value]);

        $this->actingAs($staff, 'admin')
            ->getJson('/api/v1/admin/products')
            ->assertForbidden();
    }

    public function test_user_type_enum_uses_integer_values(): void
    {
        self::assertSame(1, UserTypeEnum::Customer->value);
        self::assertSame(2, UserTypeEnum::Staff->value);
    }

    public function test_customer_cannot_read_admin_customer_data(): void
    {
        $customer = User::factory()->create(['user_type' => UserTypeEnum::Customer->value]);

        $this->actingAs($customer, 'customer')
            ->getJson('/api/v1/admin/customers')
            ->assertUnauthorized();
    }

    public function test_staff_with_the_owner_role_can_read_an_authorized_section(): void
    {
        $staff = $this->ownerStaff();

        $this->actingAs($staff, 'admin')
            ->getJson('/api/v1/admin/products')
            ->assertOk();
    }

    public function test_customer_order_listing_is_scoped_to_the_authenticated_customer(): void
    {
        $firstCustomer = User::factory()->create(['user_type' => UserTypeEnum::Customer->value]);
        $secondCustomer = User::factory()->create(['user_type' => UserTypeEnum::Customer->value]);
        $firstOrder = $this->orderFor($firstCustomer);
        $variant = $this->variantWithPrimaryImage();
        $firstOrder->items()->create([
            'product_variant_id' => $variant->id,
            'product_name' => 'Soft cotton sleepsuit',
            'sku' => 'BEB-000001-01',
            'quantity' => 2,
            'unit_price_amount' => 12000,
            'total_amount' => 24000,
        ]);
        $firstOrder->addresses()->create([
            'type' => AddressTypeEnum::Shipping->value,
            'recipient_name' => $firstCustomer->name,
            'phone' => '+21620000000',
            'line_one' => '1 Rue des Fleurs',
            'city' => 'Tunis',
            'country_code' => 'TN',
        ]);
        $firstOrder->payments()->create([
            'method' => 'cash_on_delivery',
            'status' => OrderStatusEnum::PENDING->value,
            'amount' => 24000,
            'currency_code' => 'TND',
        ]);
        foreach (range(1, 10) as $number) {
            Order::query()->create([
                'number' => "BB-PAGE-{$number}",
                'user_id' => $firstCustomer->id,
                'customer_name' => $firstCustomer->name,
                'customer_email' => $firstCustomer->email,
                'status' => OrderStatusEnum::PENDING->value,
                'total_amount' => 1000,
            ]);
        }
        $this->orderFor($secondCustomer);

        $this->actingAs($firstCustomer, 'customer')
            ->getJson('/api/v1/customer/orders?page=4')
            ->assertOk()
            ->assertJsonCount(2, 'data.items')
            ->assertJsonPath('data.currentPage', 4)
            ->assertJsonPath('data.lastPage', 4)
            ->assertJsonPath('data.perPage', 3)
            ->assertJsonPath('data.total', 11)
            ->assertJsonPath('data.items.1.id', $firstOrder->id)
            ->assertJsonPath('data.items.1.items.0.productName', 'Soft cotton sleepsuit')
            ->assertJsonPath('data.items.1.items.0.quantity', 2)
            ->assertJsonPath('data.items.1.items.0.productImageUrl', rtrim((string) config('app.url'), '/').'/storage/products/sleepsuit.jpg')
            ->assertJsonPath('data.items.1.shippingAddress.city', 'Tunis')
            ->assertJsonPath('data.items.1.shippingAddress.phone', '+21620000000')
            ->assertJsonPath('data.items.1.payment.method', 'cash_on_delivery');
    }

    private function ownerStaff(): User
    {
        $owner = Role::query()->create(['name' => 'owner', 'title' => 'Owner']);
        $this->seed(StaffAccessSeeder::class);
        $staff = User::factory()->create(['user_type' => UserTypeEnum::Staff->value]);
        $staff->roles()->attach($owner);

        return $staff;
    }

    private function orderFor(User $customer): Order
    {
        return Order::query()->create([
            'number' => 'BB-'.str_pad((string) $customer->id, 6, '0', STR_PAD_LEFT),
            'user_id' => $customer->id,
            'customer_name' => $customer->name,
            'customer_email' => $customer->email,
            'status' => OrderStatusEnum::PENDING->value,
            'total_amount' => 1000,
        ]);
    }

    private function variantWithPrimaryImage(): ProductVariant
    {
        $product = Product::query()->create([
            'status' => ProductStatusEnum::Published->value,
            'price_amount' => 12000,
            'stock_quantity' => 2,
            'low_stock_threshold' => 1,
        ]);
        $variant = $product->allVariants()->create([
            'sku' => 'BEB-000001-01',
            'is_default' => true,
            'is_active' => true,
        ]);
        Asset::query()->create([
            'resource_type' => 'product',
            'resource_id' => $product->id,
            'disk' => 'public',
            'path' => 'products/sleepsuit.jpg',
            'original_name' => 'sleepsuit.jpg',
            'mime_type' => 'image/jpeg',
            'size' => 1024,
            'sort_order' => 0,
            'primary_image' => true,
        ]);

        return $variant;
    }
}
