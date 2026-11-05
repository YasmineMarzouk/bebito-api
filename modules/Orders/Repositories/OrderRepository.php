<?php

namespace Modules\Orders\Repositories;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Str;
use Modules\Catalog\Contracts\ValueObjects\ReservedProductVariant;
use Modules\Orders\Contracts\Repositories\OrderRepositoryInterface;
use Modules\Orders\Models\Order;
use Modules\Promotions\Contracts\ValueObjects\CouponApplication;

final class OrderRepository implements OrderRepositoryInterface
{
    public function all(?int $customerId = null): Collection
    {
        return Order::query()->when($customerId !== null, fn ($query) => $query->where('user_id', $customerId))->orderByDesc('id')->get();
    }

    public function find(int $id): ?Order
    {
        return Order::query()->find($id);
    }

    public function applyCoupon(Order $order, CouponApplication $coupon, int $grossAmount): Order
    {
        $order->update([
            'coupon_id' => $coupon->couponId,
            'coupon_code' => $coupon->couponCode,
            'coupon_discount_amount' => $coupon->discountAmount,
            'total_amount' => max(0, $grossAmount - $coupon->discountAmount),
        ]);

        return $order->refresh();
    }

    public function createCheckout(?int $userId, array $customer, array $items, int $shippingAmount, string $currencyCode): Order
    {
        $subtotal = array_sum(array_map(
            fn (ReservedProductVariant $item): int => $item->unitPriceAmount * $item->quantity,
            $items,
        ));
        $order = Order::query()->create([
            'number' => 'BB-'.now()->format('Ymd').'-'.Str::upper(Str::random(6)),
            'user_id' => $userId,
            'customer_name' => trim($customer['firstName'].' '.$customer['lastName']),
            'customer_email' => $customer['email'],
            'currency_code' => $currencyCode,
            'status' => 'pending',
            'payment_method_code' => 'cash_on_delivery',
            'total_amount' => $subtotal + $shippingAmount,
        ]);

        foreach ($items as $item) {
            $order->items()->create([
                'product_variant_id' => $item->variantId,
                'product_name' => $item->productName,
                'sku' => $item->sku,
                'quantity' => $item->quantity,
                'unit_price_amount' => $item->unitPriceAmount,
                'total_amount' => $item->unitPriceAmount * $item->quantity,
            ]);
        }

        $order->addresses()->create([
            'type' => 'shipping',
            'recipient_name' => trim($customer['firstName'].' '.$customer['lastName']),
            'phone' => $customer['phone'],
            'line_one' => $customer['address'],
            'city' => $customer['city'],
            'country_code' => 'TN',
        ]);
        $order->payments()->create([
            'method' => 'cash_on_delivery',
            'status' => 'pending',
            'amount' => $subtotal + $shippingAmount,
            'currency_code' => $currencyCode,
        ]);
        $order->statusHistories()->create(['status' => 'pending', 'note' => $customer['deliveryNotes']]);

        return $order;
    }
}
