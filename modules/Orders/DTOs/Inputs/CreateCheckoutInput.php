<?php

namespace Modules\Orders\DTOs\Inputs;

final readonly class CreateCheckoutInput
{
    /**
     * @param  array<int, array{catalogItemType: string, variantId: int|null, packVariantId: int|null, quantity: int}>  $items
     */
    public function __construct(
        public string $firstName,
        public string $lastName,
        public string $email,
        public string $phone,
        public string $address,
        public string $city,
        public ?string $postalCode,
        public ?string $deliveryNotes,
        public ?string $couponCode,
        public string $idempotencyKey,
        public array $items,
    ) {}

    /** @param array<string, mixed> $data */
    public static function from(array $data): self
    {
        return new self(
            (string) $data['first_name'],
            (string) $data['last_name'],
            (string) $data['email'],
            (string) $data['phone'],
            (string) $data['address'],
            (string) $data['city'],
            isset($data['postal_code']) ? (string) $data['postal_code'] : null,
            $data['delivery_notes'] ?? null,
            isset($data['coupon_code']) ? strtoupper(trim((string) $data['coupon_code'])) : null,
            (string) $data['idempotency_key'],
            array_map(fn (array $item): array => [
                'catalogItemType' => (string) ($item['catalog_item_type'] ?? 'product'),
                'variantId' => isset($item['variant_id']) ? (int) $item['variant_id'] : null,
                'packVariantId' => isset($item['pack_variant_id']) ? (int) $item['pack_variant_id'] : null,
                'quantity' => (int) $item['quantity'],
            ], $data['items']),
        );
    }

    public function idempotencyFingerprint(?int $userId): string
    {
        $items = $this->items;

        usort($items, static fn (array $left, array $right): int => [$left['catalogItemType'], $left['variantId'], $left['packVariantId']] <=> [$right['catalogItemType'], $right['variantId'], $right['packVariantId']]);

        return hash('sha256', json_encode([
            'userId' => $userId,
            'firstName' => $this->firstName,
            'lastName' => $this->lastName,
            'email' => $this->email,
            'phone' => $this->phone,
            'address' => $this->address,
            'city' => $this->city,
            'postalCode' => $this->postalCode,
            'deliveryNotes' => $this->deliveryNotes,
            'couponCode' => $this->couponCode,
            'items' => $items,
        ], JSON_THROW_ON_ERROR));
    }
}
