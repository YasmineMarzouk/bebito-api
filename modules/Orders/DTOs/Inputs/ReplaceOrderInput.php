<?php

namespace Modules\Orders\DTOs\Inputs;

final readonly class ReplaceOrderInput
{
    /**
     * @param  array<int, array{catalogItemType: string, variantId: int|null, packVariantId: int|null, quantity: int}>  $items
     * @param  array{recipientName: string, phone: string, lineOne: string, city: string, postalCode: ?string}|null  $shippingAddress
     */
    public function __construct(public array $items, public ?int $couponId, public ?array $shippingAddress, public ?string $note) {}

    /** @param array{items: array<int, array{variant_id: int, quantity: int}>, coupon_id?: int|null, shipping_address?: array{recipient_name: string, phone: string, line_one: string, city: string, postal_code?: string|null}, note?: string|null} $data */
    public static function from(array $data): self
    {
        return new self(
            array_map(fn (array $item): array => [
                'catalogItemType' => (string) ($item['catalog_item_type'] ?? 'product'),
                'variantId' => isset($item['variant_id']) ? (int) $item['variant_id'] : null,
                'packVariantId' => isset($item['pack_variant_id']) ? (int) $item['pack_variant_id'] : null,
                'quantity' => (int) $item['quantity'],
            ], $data['items']),
            array_key_exists('coupon_id', $data) && $data['coupon_id'] !== null ? (int) $data['coupon_id'] : null,
            isset($data['shipping_address']) ? [
                'recipientName' => (string) $data['shipping_address']['recipient_name'],
                'phone' => (string) $data['shipping_address']['phone'],
                'lineOne' => (string) $data['shipping_address']['line_one'],
                'city' => (string) $data['shipping_address']['city'],
                'postalCode' => $data['shipping_address']['postal_code'] ?? null,
            ] : null,
            $data['note'] ?? null,
        );
    }
}
