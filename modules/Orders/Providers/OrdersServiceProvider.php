<?php

namespace Modules\Orders\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\Orders\Contracts\Repositories\OrderRepositoryInterface;
use Modules\Orders\Contracts\Services\CustomerOrderStatisticsServiceInterface;
use Modules\Orders\Repositories\OrderRepository;
use Modules\Orders\Services\CustomerOrderStatisticsService;

final class OrdersServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(OrderRepositoryInterface::class, OrderRepository::class);
        $this->app->bind(CustomerOrderStatisticsServiceInterface::class, CustomerOrderStatisticsService::class);
    }
}
