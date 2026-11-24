<?php

namespace Modules\Orders\Services;

use Modules\Core\Contracts\Repositories\SettingsRepositoryInterface;
use Modules\Core\Models\Currency;
use Modules\Orders\Exceptions\CheckoutUnavailableException;

final class ShippingAmountCalculator
{
    public function __construct(private readonly SettingsRepositoryInterface $settings) {}

    public function forSubtotal(int $subtotalAmount, string $currencyCode): int
    {
        $currency = Currency::query()
            ->whereKey($currencyCode)
            ->where('is_active', true)
            ->lockForUpdate()
            ->first();

        if ($currency === null || $currency->decimal_places !== 3) {
            throw new CheckoutUnavailableException;
        }

        $settings = $this->settings->values();
        $multiplier = 10 ** $currency->decimal_places;
        $freeShippingThreshold = (int) ($settings['shipping.free_shipping_threshold'] ?? 0) * $multiplier;

        return $subtotalAmount >= $freeShippingThreshold
            ? 0
            : (int) ($settings['shipping.standard_shipping_amount'] ?? 0) * $multiplier;
    }
}
