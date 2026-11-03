<?php

namespace Modules\Customer\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\Customer\Contracts\Repositories\CustomerRepositoryInterface;
use Modules\Customer\Contracts\Services\CustomerBadgeResolverInterface;
use Modules\Customer\Repositories\CustomerRepository;
use Modules\Customer\Services\CustomerBadgeResolver;

final class CustomerServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(CustomerRepositoryInterface::class, CustomerRepository::class);
        $this->app->bind(CustomerBadgeResolverInterface::class, CustomerBadgeResolver::class);
    }
}
