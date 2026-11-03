<?php

namespace Tests\Unit\Promotions;

use Mockery;
use Modules\Promotions\Contracts\Repositories\CouponRepositoryInterface;
use Modules\Promotions\Exceptions\CouponNotApplicableException;
use Modules\Promotions\Models\Coupon;
use Modules\Promotions\Services\CouponApplicationService;
use Tests\TestCase;

class CouponApplicationServiceTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();

        parent::tearDown();
    }

    public function test_it_calculates_a_percentage_discount(): void
    {
        $coupon = new Coupon;
        $coupon->setRawAttributes([
            'id' => 12,
            'code' => 'WELCOME10',
            'type' => 'percentage',
            'percentage' => 10,
            'fixed_amount' => null,
            'min_order_amount' => 5000,
            'usage_limit' => null,
            'usage_count' => 0,
            'expires_at' => null,
            'is_active' => true,
        ]);

        $coupons = Mockery::mock(CouponRepositoryInterface::class);
        $coupons->shouldReceive('find')->once()->with(12)->andReturn($coupon);

        $application = (new CouponApplicationService($coupons))->calculate(12, 25000);

        self::assertSame(12, $application->couponId);
        self::assertSame('WELCOME10', $application->couponCode);
        self::assertSame(2500, $application->discountAmount);
    }

    public function test_it_rejects_an_inactive_coupon(): void
    {
        $coupon = new Coupon;
        $coupon->setRawAttributes([
            'id' => 12,
            'code' => 'PAUSED',
            'type' => 'percentage',
            'percentage' => 10,
            'min_order_amount' => 0,
            'usage_count' => 0,
            'is_active' => false,
        ]);

        $coupons = Mockery::mock(CouponRepositoryInterface::class);
        $coupons->shouldReceive('find')->once()->with(12)->andReturn($coupon);

        $this->expectException(CouponNotApplicableException::class);

        (new CouponApplicationService($coupons))->calculate(12, 25000);
    }
}
