<?php

namespace Modules\Orders\Repositories;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Str;
use Modules\Catalog\Contracts\ValueObjects\ReservedProductVariant;
use Modules\Orders\Contracts\Repositories\OrderRepositoryInterface;
use Modules\Orders\Enums\AddressTypeEnum;
use Modules\Orders\Enums\OrderStatusEnum;
use Modules\Orders\Enums\OrderTypeEnum;
use Modules\Orders\Enums\OrderItemTypeEnum;
use Modules\Orders\Enums\PaymentMethodEnum;
use Modules\Orders\Enums\PaymentStatusEnum;
use Modules\Orders\Models\Order;
use Modules\Promotions\Contracts\ValueObjects\CouponApplication;

final class OrderRepository implements OrderRepositoryInterface
{
    public function all(?int $customerId = null): Collection
    {
        return Order::query()
            ->with(['items.productVariant.product.assets', 'items.packVariant.pack.assets', 'items.packComponents', 'addresses', 'payments', 'statusHistories'])
            ->when($customerId !== null, fn ($query) => $query->where('user_id', $customerId))
            ->orderByDesc('id')
            ->get();
    }

    public function paginateForAdmin(?int $customerId, int $perPage, int $page): LengthAwarePaginator
    {
        return Order::query()
            ->with(['items.productVariant.product.assets', 'items.packVariant.pack.assets', 'items.packComponents', 'addresses', 'payments', 'statusHistories'])
            ->when($customerId !== null, fn ($query) => $query->where('user_id', $customerId))
            ->orderByDesc('id')
            ->paginate($perPage, ['*'], 'page', $page);
    }

    /** @return LengthAwarePaginator<int, Order> */
    public function paginateForCustomer(int $customerId, int $page): LengthAwarePaginator
    {
        return Order::query()
            ->with(['items.productVariant.product.assets', 'items.packVariant.pack.assets', 'items.packComponents', 'addresses', 'payments', 'statusHistories'])
            ->where('user_id', $customerId)
            ->orderByDesc('id')
            ->paginate(2, ['*'], 'page', $page);
    }

    public function find(int $id): ?Order
    {
        return Order::query()->find($id);
    }

    public function findForUpdate(int $id): ?Order
    {
        return Order::query()->lockForUpdate()->find($id);
    }

    public function findForManagementForUpdate(int $id): ?Order
    {
        return Order::query()
            ->with(['items.packComponents', 'addresses', 'payments'])
            ->lockForUpdate()
            ->find($id);
    }

    public function findByIdempotencyKeyForUpdate(string $idempotencyKey): ?Order
    {
        return Order::query()->where('idempotency_key', $idempotencyKey)->lockForUpdate()->first();
    }

    public function applyCoupon(Order $order, CouponApplication $coupon, int $grossAmount): Order
    {
        $totalAmount = max(0, $grossAmount - $coupon->discountAmount);

        $order->update([
            'coupon_id' => $coupon->couponId,
            'coupon_code' => $coupon->couponCode,
            'coupon_discount_amount' => $coupon->discountAmount,
            'total_amount' => $totalAmount,
        ]);
        $order->payments()
            ->where('status', PaymentStatusEnum::Pending->value)
            ->update(['amount' => $totalAmount]);

        return $order->refresh();
    }

    public function createCheckout(?int $userId, array $customer, array $items, int $shippingAmount, string $currencyCode, string $idempotencyKey, string $idempotencyFingerprint, ?CouponApplication $coupon = null): Order
    {
        $subtotal = array_sum(array_map(
            fn (ReservedProductVariant $item): int => $item->unitPriceAmount * $item->quantity,
            $items,
        ));
        $order = Order::query()->create([
            'number' => 'BB-'.now()->format('Ymd').'-'.Str::upper((string) Str::ulid()),
            'idempotency_key' => $idempotencyKey,
            'idempotency_fingerprint' => $idempotencyFingerprint,
            'user_id' => $userId,
            'customer_name' => trim($customer['firstName'].' '.$customer['lastName']),
            'customer_email' => $customer['email'],
            'currency_code' => $currencyCode,
            'status' => OrderStatusEnum::PENDING->value,
            'order_type' => OrderTypeEnum::DELIVERY->value,
            'payment_method_code' => PaymentMethodEnum::CashOnDelivery->value,
            'coupon_id' => $coupon?->couponId,
            'coupon_code' => $coupon?->couponCode,
            'coupon_discount_amount' => $coupon?->discountAmount ?? 0,
            'subtotal_amount' => $subtotal,
            'shipping_amount' => $shippingAmount,
            'total_amount' => max(0, $subtotal + $shippingAmount - ($coupon?->discountAmount ?? 0)),
        ]);

        foreach ($items as $item) {
            $orderItem = $order->items()->create([
                'product_variant_id' => $item->catalogItemType === OrderItemTypeEnum::Product ? $item->variantId : null,
                'catalog_item_type' => $item->catalogItemType->value,
                'pack_variant_id' => $item->packVariantId,
                'pack_snapshot' => $item->packSnapshot,
                'product_name' => $item->productName,
                'sku' => $item->sku,
                'variant_attributes' => $item->attributes,
                'quantity' => $item->quantity,
                'unit_price_amount' => $item->unitPriceAmount,
                'total_amount' => $item->unitPriceAmount * $item->quantity,
            ]);

            if ($item->catalogItemType === OrderItemTypeEnum::Pack) {
                foreach ($item->componentSnapshots as $component) {
                    $orderItem->packComponents()->create([
                        'product_id' => $component['productId'],
                        'product_variant_id' => $component['productVariantId'],
                        'product_name' => $component['productName'],
                        'product_sku' => $component['productSku'],
                        'variant_sku' => $component['variantSku'],
                        'variant_attributes' => $component['variantAttributes'],
                        'quantity_per_pack' => $component['quantityPerPack'],
                        'quantity_consumed' => $component['quantity'],
                        'unit_price_amount' => $component['unitPriceAmount'],
                        'total_amount' => $component['unitPriceAmount'] * $component['quantity'],
                        'component_snapshot' => [
                            'product_id' => $component['productId'],
                            'product_variant_id' => $component['productVariantId'],
                        ],
                    ]);
                }
            }
        }

        $order->addresses()->create([
            'type' => AddressTypeEnum::Shipping->value,
            'recipient_name' => trim($customer['firstName'].' '.$customer['lastName']),
            'phone' => $customer['phone'],
            'line_one' => $customer['address'],
            'city' => $customer['city'],
            'postal_code' => $customer['postalCode'],
            'country_code' => 'TN',
        ]);
        $order->payments()->create([
            'method' => PaymentMethodEnum::CashOnDelivery->value,
            'status' => PaymentStatusEnum::Pending->value,
            'amount' => $order->total_amount,
            'currency_code' => $currencyCode,
        ]);
        $order->statusHistories()->create(['status' => OrderStatusEnum::PENDING->value, 'note' => $customer['deliveryNotes']]);

        return $order;
    }
}
