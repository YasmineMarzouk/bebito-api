<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Modules\Catalog\Events\CollectionPublished;
use Modules\Catalog\Mail\CollectionPublishedMail;
use Modules\Catalog\Models\Collection;
use Modules\Catalog\Models\Product;
use Modules\Catalog\Models\ProductVariant;
use Modules\Catalog\Enums\ProductStatusEnum;
use Modules\Core\Enums\UserTypeEnum;
use Modules\Core\Models\Country;
use Modules\Core\Models\Currency;
use Modules\Customer\Mail\WelcomeCustomerMail;
use Modules\Customer\Models\CustomerProfile;
use Modules\Orders\Mail\OrderReceivedMail;
use Tests\TestCase;

class NotificationEmailTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('app.env', 'production');
        Currency::query()->create(['code' => 'TND', 'name' => 'Tunisian dinar', 'symbol' => 'TND', 'decimal_places' => 3, 'is_active' => true]);
        Country::query()->create(['code' => 'TN', 'name' => 'Tunisia', 'is_active' => true]);
    }

    public function test_registration_queues_a_single_welcome_email(): void
    {
        Mail::fake();

        $this->postJson('/api/v1/customer/auth/register', [
            'name' => 'Ada Customer',
            'email' => 'ada@example.test',
            'password' => 'secure-password',
            'password_confirmation' => 'secure-password',
            'phone' => '+216 20 000 000',
            'shopping_for' => 'gift',
        ])->assertCreated();

        Mail::assertQueued(WelcomeCustomerMail::class, function (WelcomeCustomerMail $mail): bool {
            return $mail->hasTo('ada@example.test') && $mail->customerName === 'Ada Customer';
        });
        Mail::assertQueuedCount(1);
    }

    public function test_checkout_queues_one_order_received_email_with_persisted_snapshots(): void
    {
        Mail::fake();
        $variant = $this->sellableVariant();
        $payload = $this->checkoutPayload($variant->id);

        $this->postJson('/api/v1/store/checkout', $payload, ['Idempotency-Key' => '4b6ec3de-530e-48a4-8e6f-2ab32b7075b7'])->assertCreated();
        $this->postJson('/api/v1/store/checkout', $payload, ['Idempotency-Key' => '4b6ec3de-530e-48a4-8e6f-2ab32b7075b7'])->assertCreated();

        $this->assertDatabaseHas('order_items', [
            'product_variant_id' => $variant->id,
            'variant_attributes' => json_encode(['size' => '3-6m', 'color' => 'Sage']),
        ]);
        $this->assertDatabaseHas('orders', [
            'subtotal_amount' => 25000,
            'shipping_amount' => 0,
            'total_amount' => 25000,
        ]);
        Mail::assertQueued(OrderReceivedMail::class, function (OrderReceivedMail $mail): bool {
            return $mail->hasTo('marie@example.test')
                && $mail->order->subtotal_amount === 25000
                && $mail->order->shipping_amount === 0;
        });
        Mail::assertQueuedCount(1);
    }

    public function test_new_collection_email_is_queued_only_for_marketing_opted_in_customers(): void
    {
        Mail::fake();
        $subscribedCustomer = User::factory()->create(['user_type' => UserTypeEnum::Customer->value]);
        $unsubscribedCustomer = User::factory()->create(['user_type' => UserTypeEnum::Customer->value]);
        CustomerProfile::query()->create(['user_id' => $subscribedCustomer->id, 'marketing_opt_in' => true]);
        CustomerProfile::query()->create(['user_id' => $unsubscribedCustomer->id, 'marketing_opt_in' => false]);

        $collection = Collection::query()->create([
            'name' => 'Nouvelle collection',
            'slug' => 'nouvelle-collection',
            'is_active' => true,
        ]);

        event(new CollectionPublished($collection->id));

        Mail::assertQueued(CollectionPublishedMail::class, function (CollectionPublishedMail $mail) use ($subscribedCustomer, $collection): bool {
            return $mail->hasTo($subscribedCustomer->email)
                && $mail->collection->id === $collection->id;
        });
        Mail::assertNotQueued(CollectionPublishedMail::class, function (CollectionPublishedMail $mail) use ($unsubscribedCustomer): bool {
            return $mail->hasTo($unsubscribedCustomer->email);
        });
        Mail::assertQueuedCount(1);
    }

    private function sellableVariant(): ProductVariant
    {
        $product = Product::query()->create([
            'status' => ProductStatusEnum::Published->value,
            'sku' => 'BEB-EMAIL-001',
            'price_amount' => 12500,
            'stock_quantity' => 5,
            'low_stock_threshold' => 1,
        ]);
        $product->translations()->create(['locale_code' => 'fr', 'name' => 'Body en coton', 'slug' => 'body-en-coton']);
        $variant = $product->variants()->create([
            'sku' => 'BEB-EMAIL-001-01',
            'attributes' => ['size' => '3-6m', 'color' => 'Sage'],
            'is_default' => true,
        ]);
        $variant->inventoryItem()->create(['quantity' => 5, 'low_stock_threshold' => 1]);

        return $variant;
    }

    /** @return array<string, mixed> */
    private function checkoutPayload(int $variantId): array
    {
        return [
            'first_name' => 'Marie',
            'last_name' => 'Dupont',
            'email' => 'marie@example.test',
            'phone' => '+21620000000',
            'address' => '1 Rue des Fleurs',
            'city' => 'Tunis',
            'items' => [['variant_id' => $variantId, 'quantity' => 2]],
        ];
    }
}
