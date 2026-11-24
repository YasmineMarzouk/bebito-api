<?php

namespace Modules\Orders\DTOs\Outputs;

use Modules\Orders\Models\Order;

final readonly class OrderListOutput
{
    /**
     * @param  array<int, array{productVariantId: int|null, productName: string, sku: string, variantAttributes: array<string, mixed>, quantity: int, unitPriceAmount: int, compareAtPriceAmount: int|null, discountPercentage: float|null, totalAmount: int, productImageUrl: string|null}>  $items
     * @param  array{recipientName: string, phone: string, lineOne: string, city: string, postalCode: string|null, countryCode: string}|null  $shippingAddress
     * @param  array{method: string, status: int, amount: int, currencyCode: string}|null  $payment
     * @param  array<int, array{status: int, note: string|null, changedAt: string}>  $statusHistory
     */
    public function __construct(public int $id, public ?int $userId, public string $number, public string $customerName, public string $customerEmail, public int $status, public int $orderType, public string $paymentMethodCode, public ?int $couponId, public ?string $couponCode, public int $couponDiscountAmount, public int $subtotalAmount, public int $shippingAmount, public int $totalAmount, public string $currencyCode, public string $createdAt, public array $items, public ?array $shippingAddress, public ?array $payment, public array $statusHistory) {}

    public static function fromModel(Order $order): self
    {
        $shippingAddress = $order->addresses->firstWhere('type.value', \Modules\Orders\Enums\AddressTypeEnum::Shipping->value);
        $payment = $order->payments->first();
        $itemSubtotalAmount = (int) $order->items->sum('total_amount');
        $usesLegacyAmountSnapshot = $order->subtotal_amount === 0 && $itemSubtotalAmount > 0;
        $subtotalAmount = $usesLegacyAmountSnapshot ? $itemSubtotalAmount : $order->subtotal_amount;
        $shippingAmount = $usesLegacyAmountSnapshot
            ? max(0, $order->total_amount + $order->coupon_discount_amount - $subtotalAmount)
            : $order->shipping_amount;

        return new self(
            $order->id,
            $order->user_id,
            $order->number,
            $order->customer_name,
            $order->customer_email,
            $order->status->value,
            $order->order_type->value,
            $order->payment_method_code->value,
            $order->coupon_id,
            $order->coupon_code,
            $order->coupon_discount_amount,
            $subtotalAmount,
            $shippingAmount,
            $order->total_amount,
            $order->currency_code,
            $order->created_at->toDateString(),
            $order->items->map(function ($item): array {
                if ($item->catalog_item_type === \Modules\Orders\Enums\OrderItemTypeEnum::Pack) {
                    $packSnapshot = $item->pack_snapshot ?? [];
                    $image = $item->packVariant?->pack?->assets->firstWhere('primary_image', true);

                    return [
                        'productVariantId' => null,
                        'packVariantId' => $item->pack_variant_id,
                        'catalogItemType' => 'pack',
                        'productName' => $item->product_name,
                        'sku' => $item->sku,
                        'variantAttributes' => $item->variant_attributes ?? $packSnapshot['pack_variant_attributes'] ?? [],
                        'quantity' => $item->quantity,
                        'unitPriceAmount' => $item->unit_price_amount,
                        'compareAtPriceAmount' => null,
                        'discountPercentage' => null,
                        'totalAmount' => $item->total_amount,
                        'productImageUrl' => $image === null ? null : rtrim((string) config('app.url'), '/').'/storage/'.ltrim($image->path, '/'),
                    ];
                }

                $product = $item->productVariant?->product;
                $primaryAsset = $product?->assets->firstWhere('primary_image', true);
                $compareAtPriceAmount = $product?->compare_at_price_amount;
                $variantAttributes = $item->variant_attributes;

                if ($variantAttributes === null || $variantAttributes === []) {
                    $variantAttributes = $item->productVariant?->attributes ?? [];
                }

                return [
                    'productVariantId' => $item->product_variant_id,
                    'packVariantId' => null,
                    'catalogItemType' => 'product',
                    'productName' => $item->product_name,
                    'sku' => $item->sku,
                    'variantAttributes' => $variantAttributes,
                    'quantity' => $item->quantity,
                    'unitPriceAmount' => $item->unit_price_amount,
                    'compareAtPriceAmount' => $compareAtPriceAmount !== null && $compareAtPriceAmount > $item->unit_price_amount ? $compareAtPriceAmount : null,
                    'discountPercentage' => $compareAtPriceAmount !== null && $compareAtPriceAmount > $item->unit_price_amount ? $product->discount_percentage : null,
                    'totalAmount' => $item->total_amount,
                    'productImageUrl' => $primaryAsset === null ? null : rtrim((string) config('app.url'), '/').'/storage/'.ltrim($primaryAsset->path, '/'),
                ];
            })->all(),
            $shippingAddress === null ? null : [
                'recipientName' => $shippingAddress->recipient_name,
                'phone' => $shippingAddress->phone,
                'lineOne' => $shippingAddress->line_one,
                'city' => $shippingAddress->city,
                'postalCode' => $shippingAddress->postal_code,
                'countryCode' => $shippingAddress->country_code,
            ],
            $payment === null ? null : [
                'method' => $payment->method->value,
                'status' => $payment->status->value,
                'amount' => $payment->amount,
                'currencyCode' => $payment->currency_code,
            ],
            $order->statusHistories->map(fn ($history): array => [
                'status' => $history->status->value,
                'note' => $history->note,
                'changedAt' => $history->created_at->toISOString(),
            ])->all(),
        );
    }
}
