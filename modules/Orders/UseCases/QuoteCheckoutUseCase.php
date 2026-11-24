<?php

namespace Modules\Orders\UseCases;

use Modules\Catalog\Contracts\Repositories\CheckoutInventoryRepositoryInterface;
use Modules\Orders\DTOs\Inputs\QuoteCheckoutInput;
use Modules\Orders\DTOs\Outputs\CheckoutQuoteOutput;
use Modules\Orders\Services\ShippingAmountCalculator;
use Modules\Promotions\Contracts\Services\CouponApplicationServiceInterface;

final class QuoteCheckoutUseCase
{
    public function __construct(
        private readonly CheckoutInventoryRepositoryInterface $inventory,
        private readonly ShippingAmountCalculator $shipping,
        private readonly CouponApplicationServiceInterface $coupons,
    ) {}

    public function execute(QuoteCheckoutInput $input): CheckoutQuoteOutput
    {
        $items = $this->inventory->quote($input->items);
        $subtotal = array_sum(array_map(fn ($item): int => $item->unitPriceAmount * $item->quantity, $items));
        $currencyCode = 'TND';
        $shippingAmount = $this->shipping->forSubtotal($subtotal, $currencyCode);
        $coupon = $this->coupons->previewByCode($input->couponCode, $subtotal + $shippingAmount);

        return CheckoutQuoteOutput::fromCoupon($coupon, $subtotal, $shippingAmount, $currencyCode);
    }
}
