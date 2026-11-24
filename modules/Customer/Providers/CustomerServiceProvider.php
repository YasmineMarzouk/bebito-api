<?php

namespace Modules\Customer\Providers;

use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;
use Modules\Customer\Contracts\Repositories\CustomerRepositoryInterface;
use Modules\Customer\Contracts\Services\CustomerBadgeResolverInterface;
use Modules\Customer\Events\CustomerRegistered;
use Modules\Customer\Listeners\SendWelcomeEmail;
use Modules\Customer\Repositories\CustomerRepository;
use Modules\Customer\Services\CustomerBadgeResolver;

final class CustomerServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(CustomerRepositoryInterface::class, CustomerRepository::class);
        $this->app->bind(CustomerBadgeResolverInterface::class, CustomerBadgeResolver::class);
    }

    public function boot(): void
    {
        Event::listen(CustomerRegistered::class, SendWelcomeEmail::class);
    }
}
