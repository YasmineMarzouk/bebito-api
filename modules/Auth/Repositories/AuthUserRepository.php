<?php

namespace Modules\Auth\Repositories;

use App\Models\User;
use Modules\Auth\Contracts\Repositories\AuthUserRepositoryInterface;
use Modules\Core\Enums\UserTypeEnum;

final class AuthUserRepository implements AuthUserRepositoryInterface
{
    public function findByEmail(string $email): ?User
    {
        return User::query()->where('email', $email)->first();
    }

    public function hasAdministratorRole(User $user): bool
    {
        return $user->user_type === UserTypeEnum::Staff;
    }

    public function hasCustomerRole(User $user): bool
    {
        return $user->user_type === UserTypeEnum::Customer;
    }
}
