<?php

namespace Modules\Orders\Providers;

use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;
use Modules\Orders\Contracts\Repositories\OrderRepositoryInterface;
use Modules\Orders\Contracts\Services\CustomerOrderStatisticsServiceInterface;
use Modules\Orders\Events\OrderManagementNotification;
use Modules\Orders\Events\OrderPlaced;
use Modules\Orders\Listeners\SendOrderManagementEmail;
use Modules\Orders\Listeners\SendOrderReceivedEmail;
use Modules\Orders\Repositories\OrderRepository;
use Modules\Orders\Services\CustomerOrderStatisticsService;

final class OrdersServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(OrderRepositoryInterface::class, OrderRepository::class);
        $this->app->bind(CustomerOrderStatisticsServiceInterface::class, CustomerOrderStatisticsService::class);
    }

    public function boot(): void
    {
        Event::listen(OrderPlaced::class, SendOrderReceivedEmail::class);
        Event::listen(OrderManagementNotification::class, SendOrderManagementEmail::class);
    }
}
