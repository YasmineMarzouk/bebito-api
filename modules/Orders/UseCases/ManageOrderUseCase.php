<?php

namespace Modules\Orders\UseCases;

use Illuminate\Support\Facades\Event;
use Modules\Catalog\Contracts\Repositories\CheckoutInventoryRepositoryInterface;
use Modules\Catalog\Contracts\ValueObjects\ReservedProductVariant;
use Modules\Core\Contracts\Services\TransactionManagerInterface;
use Modules\Orders\Contracts\Repositories\OrderRepositoryInterface;
use Modules\Orders\DTOs\Inputs\OrderLifecycleInput;
use Modules\Orders\DTOs\Inputs\ReplaceOrderInput;
use Modules\Orders\DTOs\Outputs\OrderListOutput;
use Modules\Orders\Enums\AddressTypeEnum;
use Modules\Orders\Enums\OrderManagementNotificationTypeEnum;
use Modules\Orders\Enums\OrderStatusEnum;
use Modules\Orders\Enums\PaymentStatusEnum;
use Modules\Orders\Events\OrderManagementNotification;
use Modules\Orders\Exceptions\OrderModificationNotAllowedException;
use Modules\Orders\Exceptions\OrderNotFoundException;
use Modules\Orders\Exceptions\OrderTransitionNotAllowedException;
use Modules\Orders\Models\Order;
use Modules\Orders\Models\OrderItem;
use Modules\Orders\Enums\OrderItemTypeEnum;
use Modules\Orders\Services\ShippingAmountCalculator;
use Modules\Promotions\Contracts\Services\CouponApplicationServiceInterface;

final class ManageOrderUseCase
{
    public function __construct(
        private readonly OrderRepositoryInterface $orders,
        private readonly CheckoutInventoryRepositoryInterface $inventory,
        private readonly CouponApplicationServiceInterface $coupons,
        private readonly ShippingAmountCalculator $shipping,
        private readonly TransactionManagerInterface $transactions,
    ) {}

    public function get(int $orderId): OrderListOutput
    {
        $order = $this->orders->find($orderId);

        if ($order === null) {
            throw new OrderNotFoundException;
        }

        $order->load(['items.productVariant.product.assets', 'items.packVariant.pack.assets', 'items.packComponents', 'addresses', 'payments', 'statusHistories']);

        return OrderListOutput::fromModel($order);
    }

    public function replace(int $orderId, int $administratorId, ReplaceOrderInput $input): OrderListOutput
    {
        $wasChanged = false;
        $order = $this->transactions->run(function () use ($orderId, $administratorId, $input, &$wasChanged): Order {
            $order = $this->managedOrder($orderId);

            return $this->replaceManagedOrder($order, $administratorId, $input, $wasChanged);
        }, attempts: 3);

        if ($wasChanged) {
            Event::dispatch(new OrderManagementNotification($order->id, OrderManagementNotificationTypeEnum::Updated));
        }

        return OrderListOutput::fromModel($order);
    }

    public function applyCoupon(int $orderId, int $administratorId, int $couponId): OrderListOutput
    {
        $wasChanged = false;
        $order = $this->transactions->run(function () use ($orderId, $administratorId, $couponId, &$wasChanged): Order {
            $order = $this->managedOrder($orderId);
            $items = $order->items()->with('packComponents')->lockForUpdate()->get()
                ->map(fn (OrderItem $item): array => $this->requestedLineFromOrderItem($item))
                ->all();

            return $this->replaceManagedOrder($order, $administratorId, new ReplaceOrderInput($items, $couponId, null, 'Coupon updated by an administrator.'), $wasChanged);
        }, attempts: 3);

        if ($wasChanged) {
            Event::dispatch(new OrderManagementNotification($order->id, OrderManagementNotificationTypeEnum::Updated));
        }

        return OrderListOutput::fromModel($order);
    }

    public function confirm(int $orderId, int $administratorId, OrderLifecycleInput $input): OrderListOutput
    {
        return $this->transition($orderId, $administratorId, $input, OrderStatusEnum::PENDING, OrderStatusEnum::PROCESSING);
    }

    public function readyForDelivery(int $orderId, int $administratorId, OrderLifecycleInput $input): OrderListOutput
    {
        return $this->transition($orderId, $administratorId, $input, OrderStatusEnum::PROCESSING, OrderStatusEnum::READY, OrderManagementNotificationTypeEnum::Confirmed);
    }

    public function cancel(int $orderId, int $administratorId, OrderLifecycleInput $input): OrderListOutput
    {
        $order = $this->transactions->run(function () use ($orderId, $administratorId, $input): Order {
            $order = $this->managedOrder($orderId);

            if (! in_array($order->status, [OrderStatusEnum::PENDING, OrderStatusEnum::PROCESSING, OrderStatusEnum::READY, OrderStatusEnum::AWAITING_DELIVERY], true)) {
                throw new OrderTransitionNotAllowedException;
            }

            $existingItems = $order->items()->with('packComponents')->lockForUpdate()->get()
                ->map(fn (OrderItem $item): array => $this->existingItemPayload($item))
                ->all();
            $this->inventory->reconcileOrderReservation($order->id, $administratorId, $existingItems, []);
            $this->coupons->releaseUsage($order->coupon_id);
            $order->update(['status' => OrderStatusEnum::CANCELLED->value]);
            $order->payments()->where('status', PaymentStatusEnum::Pending->value)->update(['status' => PaymentStatusEnum::Cancelled->value]);
            $order->statusHistories()->create([
                'status' => OrderStatusEnum::CANCELLED->value,
                'changed_by' => $administratorId,
                'note' => $input->note ?? 'Order cancelled by an administrator.',
            ]);

            return $order->fresh(['items.productVariant.product.assets', 'items.packVariant.pack.assets', 'items.packComponents', 'addresses', 'payments', 'statusHistories']);
        }, attempts: 3);

        Event::dispatch(new OrderManagementNotification($order->id, OrderManagementNotificationTypeEnum::Cancelled));

        return OrderListOutput::fromModel($order);
    }

    /** @param OrderStatusEnum|array<int, OrderStatusEnum> $from */
    private function transition(int $orderId, int $administratorId, OrderLifecycleInput $input, OrderStatusEnum|array $from, OrderStatusEnum $to, ?OrderManagementNotificationTypeEnum $notification = null): OrderListOutput
    {
        $order = $this->transactions->run(function () use ($orderId, $administratorId, $input, $from, $to): Order {
            $order = $this->managedOrder($orderId);

            if (! in_array($order->status, (array) $from, true)) {
                throw new OrderTransitionNotAllowedException;
            }

            $order->update(['status' => $to->value]);
            $order->statusHistories()->create([
                'status' => $to->value,
                'changed_by' => $administratorId,
                'note' => $input->note,
            ]);

            return $order->fresh(['items.productVariant.product.assets', 'items.packVariant.pack.assets', 'items.packComponents', 'addresses', 'payments', 'statusHistories']);
        }, attempts: 3);

        if ($notification !== null) {
            Event::dispatch(new OrderManagementNotification($order->id, $notification));
        }

        return OrderListOutput::fromModel($order);
    }

    private function managedOrder(int $orderId): Order
    {
        $order = $this->orders->findForManagementForUpdate($orderId);

        if ($order === null) {
            throw new OrderNotFoundException;
        }

        return $order;
    }

    private function ensurePending(Order $order): void
    {
        if ($order->status !== OrderStatusEnum::PENDING) {
            throw new OrderModificationNotAllowedException;
        }
    }

    private function replaceManagedOrder(Order $order, int $administratorId, ReplaceOrderInput $input, bool &$wasChanged): Order
    {
        $this->ensurePending($order);
        $existingItems = $order->items()->with('packComponents')->lockForUpdate()->get()
            ->map(fn (OrderItem $item): array => $this->existingItemPayload($item))
            ->all();
        $items = $this->inventory->reconcileOrderReservation($order->id, $administratorId, $existingItems, $input->items);
        $subtotal = array_sum(array_map(fn ($item): int => $item->unitPriceAmount * $item->quantity, $items));
        $shippingAmount = $this->shipping->forSubtotal($subtotal, $order->currency_code);
        $coupon = $input->couponId === null ? null : $this->coupons->recalculate($input->couponId, $subtotal + $shippingAmount, $order->coupon_id);
        $totalAmount = max(0, $subtotal + $shippingAmount - ($coupon?->discountAmount ?? 0));

        $wasChanged = $this->itemsHaveChanged($order, $items)
            || $order->coupon_id !== $coupon?->couponId
            || $order->coupon_discount_amount !== ($coupon?->discountAmount ?? 0)
            || $order->subtotal_amount !== $subtotal
            || $order->shipping_amount !== $shippingAmount
            || $order->total_amount !== $totalAmount
            || $this->shippingAddressHasChanged($order, $input);

        if (! $wasChanged) {
            return $order->fresh(['items.productVariant.product.assets', 'items.packVariant.pack.assets', 'items.packComponents', 'addresses', 'payments', 'statusHistories']);
        }

        $this->synchroniseItems($order, $items);
        $this->synchroniseCoupon($order, $coupon?->couponId, $coupon?->couponCode, $coupon?->discountAmount ?? 0);
        $order->update(['subtotal_amount' => $subtotal, 'shipping_amount' => $shippingAmount, 'total_amount' => $totalAmount]);
        $order->payments()->where('status', PaymentStatusEnum::Pending->value)->update(['amount' => $totalAmount]);

        if ($input->shippingAddress !== null) {
            $order->addresses()->updateOrCreate(
                ['type' => AddressTypeEnum::Shipping->value],
                ['recipient_name' => $input->shippingAddress['recipientName'], 'phone' => $input->shippingAddress['phone'], 'line_one' => $input->shippingAddress['lineOne'], 'city' => $input->shippingAddress['city'], 'postal_code' => $input->shippingAddress['postalCode'], 'country_code' => 'TN'],
            );
        }

        $order->statusHistories()->create(['status' => $order->status->value, 'changed_by' => $administratorId, 'note' => $input->note ?? 'Order details updated by an administrator.']);

            return $order->fresh(['items.productVariant.product.assets', 'items.packVariant.pack.assets', 'items.packComponents', 'addresses', 'payments', 'statusHistories']);
    }

    /** @param array<int, ReservedProductVariant> $items */
    private function itemsHaveChanged(Order $order, array $items): bool
    {
        if ($order->items->count() !== count($items)) {
            return true;
        }

        $existing = $order->items->keyBy(fn (OrderItem $item): string => $item->catalog_item_type->value.'-'.($item->catalog_item_type === OrderItemTypeEnum::Pack ? $item->pack_variant_id : $item->product_variant_id));
        foreach ($items as $item) {
            $key = $item->catalogItemType->value.'-'.($item->catalogItemType === OrderItemTypeEnum::Pack ? $item->packVariantId : $item->variantId);
            $current = $existing->get($key);
            if ($current === null
                || $current->quantity !== $item->quantity
                || $current->unit_price_amount !== $item->unitPriceAmount
                || $current->total_amount !== $item->unitPriceAmount * $item->quantity
                || $current->product_name !== $item->productName
                || $current->sku !== $item->sku
                || $current->variant_attributes !== $item->attributes) {
                return true;
            }
        }

        return false;
    }

    private function shippingAddressHasChanged(Order $order, ReplaceOrderInput $input): bool
    {
        if ($input->shippingAddress === null) {
            return false;
        }

        $address = $order->addresses->firstWhere('type.value', AddressTypeEnum::Shipping->value);

        return $address === null
            || $address->recipient_name !== $input->shippingAddress['recipientName']
            || $address->phone !== $input->shippingAddress['phone']
            || $address->line_one !== $input->shippingAddress['lineOne']
            || $address->city !== $input->shippingAddress['city']
            || $address->postal_code !== $input->shippingAddress['postalCode'];
    }

    /** @param array<int, ReservedProductVariant> $items */
    private function synchroniseItems(Order $order, array $items): void
    {
        $order->items()->delete();
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
                        'component_snapshot' => ['product_id' => $component['productId'], 'product_variant_id' => $component['productVariantId']],
                    ]);
                }
            }
        }
    }

    /** @return array{catalog_item_type: string, product_variant_id: int|null, quantity: int, pack_components: array<int, array{product_variant_id: int|null, quantity_consumed: int}>} */
    private function existingItemPayload(OrderItem $item): array
    {
        return [
            'catalog_item_type' => $item->catalog_item_type->value,
            'product_variant_id' => $item->product_variant_id,
            'quantity' => $item->quantity,
            'pack_components' => $item->packComponents->map(fn ($component): array => [
                'product_variant_id' => $component->product_variant_id,
                'quantity_consumed' => $component->quantity_consumed,
            ])->all(),
        ];
    }

    /** @return array{catalogItemType: string, variantId: int|null, packVariantId: int|null, quantity: int} */
    private function requestedLineFromOrderItem(OrderItem $item): array
    {
        return [
            'catalogItemType' => $item->catalog_item_type->value,
            'variantId' => $item->catalog_item_type === OrderItemTypeEnum::Product ? $item->product_variant_id : null,
            'packVariantId' => $item->catalog_item_type === OrderItemTypeEnum::Pack ? $item->pack_variant_id : null,
            'quantity' => $item->quantity,
        ];
    }

    private function synchroniseCoupon(Order $order, ?int $couponId, ?string $couponCode, int $discountAmount): void
    {
        if ($couponId === null) {
            $this->coupons->releaseUsage($order->coupon_id);
        } else {
            $this->coupons->replaceUsage($order->coupon_id, $couponId);
        }

        $order->update([
            'coupon_id' => $couponId,
            'coupon_code' => $couponCode,
            'coupon_discount_amount' => $discountAmount,
        ]);
    }
}
