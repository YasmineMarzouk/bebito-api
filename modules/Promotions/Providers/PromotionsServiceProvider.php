<?php

namespace Modules\Promotions\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\Promotions\Contracts\Repositories\CouponRepositoryInterface;
use Modules\Promotions\Contracts\Services\CouponApplicationServiceInterface;
use Modules\Promotions\Repositories\CouponRepository;
use Modules\Promotions\Services\CouponApplicationService;

final class PromotionsServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(CouponRepositoryInterface::class, CouponRepository::class);
        $this->app->bind(CouponApplicationServiceInterface::class, CouponApplicationService::class);
    }
}
