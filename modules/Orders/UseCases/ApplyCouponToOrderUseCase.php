<?php

namespace Modules\Orders\UseCases;

use Modules\Core\Contracts\Services\TransactionManagerInterface;
use Modules\Orders\Contracts\Repositories\OrderRepositoryInterface;
use Modules\Orders\DTOs\Inputs\ApplyCouponToOrderInput;
use Modules\Orders\DTOs\Outputs\OrderCouponOutput;
use Modules\Orders\Exceptions\OrderNotFoundException;
use Modules\Promotions\Contracts\Services\CouponApplicationServiceInterface;

final class ApplyCouponToOrderUseCase
{
    public function __construct(
        private readonly OrderRepositoryInterface $orders,
        private readonly CouponApplicationServiceInterface $coupons,
        private readonly TransactionManagerInterface $transactions,
    ) {}

    public function execute(int $orderId, ApplyCouponToOrderInput $input): OrderCouponOutput
    {
        return $this->transactions->run(function () use ($orderId, $input): OrderCouponOutput {
            $order = $this->orders->find($orderId);

            if ($order === null) {
                throw new OrderNotFoundException;
            }

            $grossAmount = $order->total_amount + $order->coupon_discount_amount;
            $coupon = $this->coupons->calculate($input->couponId, $grossAmount);
            $previousCouponId = $order->coupon_id;
            $order = $this->orders->applyCoupon($order, $coupon, $grossAmount);
            $this->coupons->replaceUsage($previousCouponId, $coupon->couponId);

            return OrderCouponOutput::fromModel($order);
        });
    }
}
