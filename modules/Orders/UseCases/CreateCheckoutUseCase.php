<?php

namespace Modules\Orders\UseCases;

use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Event;
use Modules\Catalog\Contracts\Repositories\CheckoutInventoryRepositoryInterface;
use Modules\Core\Contracts\Services\TransactionManagerInterface;
use Modules\Core\Models\Country;
use Modules\Core\Models\Currency;
use Modules\Orders\Contracts\Repositories\OrderRepositoryInterface;
use Modules\Orders\DTOs\Inputs\CreateCheckoutInput;
use Modules\Orders\Events\OrderPlaced;
use Modules\Orders\Exceptions\CheckoutIdempotencyConflictException;
use Modules\Orders\Exceptions\CheckoutUnavailableException;
use Modules\Orders\Models\Order;
use Modules\Orders\Services\ShippingAmountCalculator;
use Modules\Promotions\Contracts\Services\CouponApplicationServiceInterface;

final class CreateCheckoutUseCase
{
    public function __construct(
        private readonly OrderRepositoryInterface $orders,
        private readonly CheckoutInventoryRepositoryInterface $inventory,
        private readonly ShippingAmountCalculator $shipping,
        private readonly TransactionManagerInterface $transactions,
        private readonly CouponApplicationServiceInterface $coupons,
    ) {}

    public function execute(?int $userId, CreateCheckoutInput $input): Order
    {
        $fingerprint = $input->idempotencyFingerprint($userId);
        $wasCreated = false;

        try {
            $order = $this->transactions->run(function () use ($userId, $input, $fingerprint, &$wasCreated): Order {
                $wasCreated = false;
                $existingOrder = $this->orders->findByIdempotencyKeyForUpdate($input->idempotencyKey);

                if ($existingOrder !== null) {
                    return $this->returnExistingOrder($existingOrder, $fingerprint);
                }

                $items = $this->inventory->reserve($input->items);
                $currency = $this->checkoutCurrency();
                $this->ensureTunisiaCheckoutIsAvailable();
                $subtotal = array_sum(array_map(fn ($item): int => $item->unitPriceAmount * $item->quantity, $items));
                $shippingAmount = $this->shipping->forSubtotal($subtotal, $currency->code);

                $coupon = $input->couponCode === null
                    ? null
                    : $this->coupons->calculateByCode($input->couponCode, $subtotal + $shippingAmount);

                $order = $this->orders->createCheckout($userId, [
                    'firstName' => $input->firstName,
                    'lastName' => $input->lastName,
                    'email' => $input->email,
                    'phone' => $input->phone,
                    'address' => $input->address,
                    'city' => $input->city,
                    'postalCode' => $input->postalCode,
                    'deliveryNotes' => $input->deliveryNotes,
                ], $items, $shippingAmount, $currency->code, $input->idempotencyKey, $fingerprint, $coupon);

                if ($coupon !== null) {
                    $this->coupons->replaceUsage(null, $coupon->couponId);
                }

                $this->inventory->recordOrderReservation($order->id, $userId, $items);

                $wasCreated = true;

                return $order;
            }, attempts: 3);
        } catch (QueryException $exception) {
            if (! $this->isIdempotencyKeyConflict($exception)) {
                throw $exception;
            }

            $existingOrder = $this->orders->findByIdempotencyKeyForUpdate($input->idempotencyKey);

            if ($existingOrder === null) {
                throw $exception;
            }

            $order = $this->returnExistingOrder($existingOrder, $fingerprint);
        }

        if ($wasCreated) {
            Event::dispatch(new OrderPlaced($order->id));
        }

        return $order;
    }

    private function checkoutCurrency(): Currency
    {
        $currency = Currency::query()
            ->whereKey('TND')
            ->where('is_active', true)
            ->lockForUpdate()
            ->first();

        if ($currency === null || $currency->decimal_places !== 3) {
            throw new CheckoutUnavailableException;
        }

        return $currency;
    }

    private function ensureTunisiaCheckoutIsAvailable(): void
    {
        $isTunisiaActive = Country::query()
            ->whereKey('TN')
            ->where('is_active', true)
            ->lockForUpdate()
            ->exists();

        if (! $isTunisiaActive) {
            throw new CheckoutUnavailableException;
        }
    }

    private function returnExistingOrder(Order $order, string $fingerprint): Order
    {
        if (! hash_equals((string) $order->idempotency_fingerprint, $fingerprint)) {
            throw new CheckoutIdempotencyConflictException;
        }

        return $order;
    }

    private function isIdempotencyKeyConflict(QueryException $exception): bool
    {
        return in_array($exception->getCode(), ['23000', '23505'], true)
            && str_contains(strtolower($exception->getMessage()), 'idempotency_key');
    }
}
