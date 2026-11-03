<?php

namespace Tests\Unit\Orders;

use Mockery;
use Modules\Core\Contracts\Services\TransactionManagerInterface;
use Modules\Orders\Contracts\Repositories\OrderRepositoryInterface;
use Modules\Orders\DTOs\Inputs\ApplyCouponToOrderInput;
use Modules\Orders\Models\Order;
use Modules\Orders\UseCases\ApplyCouponToOrderUseCase;
use Modules\Promotions\Contracts\Services\CouponApplicationServiceInterface;
use Modules\Promotions\Contracts\ValueObjects\CouponApplication;
use Tests\TestCase;

class ApplyCouponToOrderUseCaseTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();

        parent::tearDown();
    }

    public function test_it_replaces_an_existing_order_coupon_using_the_original_total(): void
    {
        $order = new Order;
        $order->setRawAttributes(['id' => 7, 'coupon_id' => 2, 'coupon_discount_amount' => 1000, 'total_amount' => 9000]);
        $updatedOrder = new Order;
        $updatedOrder->setRawAttributes(['id' => 7, 'coupon_id' => 4, 'coupon_code' => 'SAVE20', 'coupon_discount_amount' => 2200, 'total_amount' => 7800]);
        $application = new CouponApplication(4, 'SAVE20', 2200);

        $orders = Mockery::mock(OrderRepositoryInterface::class);
        $orders->shouldReceive('find')->once()->with(7)->andReturn($order);
        $orders->shouldReceive('applyCoupon')->once()->with($order, $application, 10000)->andReturn($updatedOrder);

        $coupons = Mockery::mock(CouponApplicationServiceInterface::class);
        $coupons->shouldReceive('calculate')->once()->with(4, 10000)->andReturn($application);
        $coupons->shouldReceive('replaceUsage')->once()->with(2, 4);

        $transactions = Mockery::mock(TransactionManagerInterface::class);
        $transactions->shouldReceive('run')->once()->andReturnUsing(fn (callable $callback) => $callback());

        $result = (new ApplyCouponToOrderUseCase($orders, $coupons, $transactions))->execute(7, new ApplyCouponToOrderInput(4));

        self::assertSame(2200, $result->couponDiscountAmount);
        self::assertSame(7800, $result->totalAmount);
    }
}
