<?php

namespace Modules\Core\DTOs\Outputs;

final readonly class SettingsOutput
{
    /** @param array<int, array{code: string, name: string, symbol: string, decimalPlaces: int}> $currencies */
    public function __construct(public string $contactEmail, public ?string $phone, public ?string $address, public ?string $instagram, public ?string $facebook, public ?string $tiktok, public ?string $pinterest, public int $standardShippingAmount, public int $freeShippingThreshold, public int $expressShippingAmount, public string $deliveryTimeDays, public string $expressDeliveryTimeDays, public string $defaultCurrency, public array $currencies) {}

    /** @param array<string, mixed> $settings @param array<int, array{code: string, name: string, symbol: string, decimalPlaces: int}> $currencies */
    public static function fromValues(array $settings, array $currencies): self
    {
        return new self((string) ($settings['store.contact_email'] ?? ''), $settings['store.phone'] ?? null, $settings['store.address'] ?? null, $settings['social.instagram'] ?? null, $settings['social.facebook'] ?? null, $settings['social.tiktok'] ?? null, $settings['social.pinterest'] ?? null, (int) ($settings['shipping.standard_shipping_amount'] ?? 0), (int) ($settings['shipping.free_shipping_threshold'] ?? 0), (int) ($settings['shipping.express_shipping_amount'] ?? 0), (string) ($settings['shipping.delivery_time_days'] ?? ''), (string) ($settings['shipping.express_delivery_time_days'] ?? ''), (string) ($settings['store.default_currency'] ?? 'TND'), $currencies);
    }
}
