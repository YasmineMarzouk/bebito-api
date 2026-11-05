<?php

namespace Modules\Orders\DTOs\Inputs;

final readonly class CreateCheckoutInput
{
    /**
     * @param  array<int, array{variantId: int, quantity: int}>  $items
     */
    public function __construct(
        public string $firstName,
        public string $lastName,
        public string $email,
        public string $phone,
        public string $address,
        public string $city,
        public ?string $deliveryNotes,
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
            $data['delivery_notes'] ?? null,
            array_map(fn (array $item): array => ['variantId' => (int) $item['variant_id'], 'quantity' => (int) $item['quantity']], $data['items']),
        );
    }
}
