<?php

namespace Modules\Orders\UseCases;

use Modules\Catalog\Contracts\Repositories\CheckoutInventoryRepositoryInterface;
use Modules\Core\Contracts\Repositories\SettingsRepositoryInterface;
use Modules\Core\Contracts\Services\TransactionManagerInterface;
use Modules\Orders\Contracts\Repositories\OrderRepositoryInterface;
use Modules\Orders\DTOs\Inputs\CreateCheckoutInput;
use Modules\Orders\Models\Order;

final class CreateCheckoutUseCase
{
    public function __construct(
        private readonly OrderRepositoryInterface $orders,
        private readonly CheckoutInventoryRepositoryInterface $inventory,
        private readonly SettingsRepositoryInterface $settings,
        private readonly TransactionManagerInterface $transactions,
    ) {}

    public function execute(?int $userId, CreateCheckoutInput $input): Order
    {
        return $this->transactions->run(function () use ($userId, $input): Order {
            $items = $this->inventory->reserve($input->items);
            $subtotal = array_sum(array_map(fn ($item): int => $item->unitPriceAmount * $item->quantity, $items));
            $settings = $this->settings->values();
            $currencyCode = (string) ($settings['store.default_currency'] ?? 'TND');
            $currency = collect($this->settings->activeCurrencies())->firstWhere('code', $currencyCode);
            $amountMultiplier = 10 ** (int) ($currency['decimalPlaces'] ?? 3);
            $freeShippingThreshold = (int) ($settings['shipping.free_shipping_threshold'] ?? 0) * $amountMultiplier;
            $shippingAmount = $subtotal >= $freeShippingThreshold
                ? 0
                : (int) ($settings['shipping.standard_shipping_amount'] ?? 0) * $amountMultiplier;

            return $this->orders->createCheckout($userId, [
                'firstName' => $input->firstName,
                'lastName' => $input->lastName,
                'email' => $input->email,
                'phone' => $input->phone,
                'address' => $input->address,
                'city' => $input->city,
                'deliveryNotes' => $input->deliveryNotes,
            ], $items, $shippingAmount, $currencyCode);
        });
    }
}
