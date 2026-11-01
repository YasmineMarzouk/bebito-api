<?php

namespace Modules\Auth\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\Auth\Contracts\Repositories\AuthUserRepositoryInterface;
use Modules\Auth\Repositories\AuthUserRepository;

final class AuthServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(AuthUserRepositoryInterface::class, AuthUserRepository::class);
    }
}
