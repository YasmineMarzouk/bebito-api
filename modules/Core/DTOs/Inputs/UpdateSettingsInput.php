<?php

namespace Modules\Core\DTOs\Inputs;

final readonly class UpdateSettingsInput
{
    public function __construct(public string $contactEmail, public ?string $phone, public ?string $address, public ?string $instagram, public ?string $facebook, public ?string $tiktok, public ?string $pinterest, public int $standardShippingAmount, public int $freeShippingThreshold, public int $expressShippingAmount, public string $deliveryTimeDays, public string $expressDeliveryTimeDays, public string $defaultCurrency) {}

    /** @param array<string, mixed> $data */
    public static function from(array $data): self
    {
        return new self((string) $data['contact_email'], $data['phone'] ?? null, $data['address'] ?? null, $data['instagram'] ?? null, $data['facebook'] ?? null, $data['tiktok'] ?? null, $data['pinterest'] ?? null, (int) $data['standard_shipping_amount'], (int) $data['free_shipping_threshold'], (int) $data['express_shipping_amount'], (string) $data['delivery_time_days'], (string) $data['express_delivery_time_days'], (string) $data['default_currency']);
    }
}
