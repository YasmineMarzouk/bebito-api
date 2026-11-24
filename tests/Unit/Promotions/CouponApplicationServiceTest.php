<?php

namespace Tests\Unit\Promotions;

use Mockery;
use Modules\Promotions\Contracts\Repositories\CouponRepositoryInterface;
use Modules\Promotions\Enums\CouponTypeEnum;
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
            'type' => CouponTypeEnum::Percentage->value,
            'percentage' => 10,
            'fixed_amount' => null,
            'min_order_amount' => 5000,
            'usage_limit' => null,
            'usage_count' => 0,
            'expires_at' => null,
            'is_active' => true,
        ]);

        $coupons = Mockery::mock(CouponRepositoryInterface::class);
        $coupons->shouldReceive('findForUpdate')->once()->with(12)->andReturn($coupon);

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
            'type' => CouponTypeEnum::Percentage->value,
            'percentage' => 10,
            'min_order_amount' => 0,
            'usage_count' => 0,
            'is_active' => false,
        ]);

        $coupons = Mockery::mock(CouponRepositoryInterface::class);
        $coupons->shouldReceive('findForUpdate')->once()->with(12)->andReturn($coupon);

        $this->expectException(CouponNotApplicableException::class);

        (new CouponApplicationService($coupons))->calculate(12, 25000);
    }

    public function test_it_rejects_an_expired_coupon_even_when_it_has_usage_remaining(): void
    {
        $coupon = new Coupon;
        $coupon->setRawAttributes([
            'id' => 13,
            'code' => 'EXPIRED',
            'type' => CouponTypeEnum::Percentage->value,
            'percentage' => 10,
            'min_order_amount' => 0,
            'usage_limit' => 100,
            'usage_count' => 0,
            'expires_at' => now()->subDay()->toDateString(),
            'is_active' => true,
        ]);

        $coupons = Mockery::mock(CouponRepositoryInterface::class);
        $coupons->shouldReceive('findForUpdate')->once()->with(13)->andReturn($coupon);

        $this->expectException(CouponNotApplicableException::class);

        (new CouponApplicationService($coupons))->calculate(13, 25000);
    }

    public function test_it_resolves_a_coupon_code_case_insensitively_under_a_lock(): void
    {
        $coupon = new Coupon;
        $coupon->setRawAttributes([
            'id' => 14,
            'code' => 'SAVE500',
            'type' => CouponTypeEnum::Fixed->value,
            'fixed_amount' => 500,
            'min_order_amount' => 0,
            'usage_count' => 0,
            'is_active' => true,
        ]);

        $coupons = Mockery::mock(CouponRepositoryInterface::class);
        $coupons->shouldReceive('findByCodeForUpdate')->once()->with('SAVE500')->andReturn($coupon);

        $application = (new CouponApplicationService($coupons))->calculateByCode(' save500 ', 1000);

        self::assertSame(14, $application->couponId);
        self::assertSame(500, $application->discountAmount);
    }

    public function test_it_rejects_a_usage_claim_when_the_database_limit_is_reached(): void
    {
        $coupons = Mockery::mock(CouponRepositoryInterface::class);
        $coupons->shouldReceive('incrementUsage')->once()->with(12)->andReturnFalse();

        $this->expectException(CouponNotApplicableException::class);

        (new CouponApplicationService($coupons))->replaceUsage(null, 12);
    }
}
