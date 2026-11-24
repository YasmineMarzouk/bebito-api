<?php

namespace Modules\Auth\Providers;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\ServiceProvider;
use Modules\Auth\Auth\UserTypeUserProvider;
use Modules\Auth\Contracts\Repositories\AuthUserRepositoryInterface;
use Modules\Auth\Contracts\Repositories\StaffPermissionRepositoryInterface;
use Modules\Auth\Repositories\AuthUserRepository;
use Modules\Auth\Repositories\StaffPermissionRepository;
use Modules\Core\Enums\UserTypeEnum;

final class AuthServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(AuthUserRepositoryInterface::class, AuthUserRepository::class);
        $this->app->bind(StaffPermissionRepositoryInterface::class, StaffPermissionRepository::class);
    }

    public function boot(): void
    {
        Auth::provider('user_type_eloquent', function ($app, array $config): UserTypeUserProvider {
            return new UserTypeUserProvider(
                $app['hash'],
                $config['model'],
                UserTypeEnum::from($config['user_type']),
            );
        });
    }
}
