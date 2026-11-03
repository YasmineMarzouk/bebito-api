<?php

namespace Modules\Auth\Repositories;

use App\Models\User;
use Modules\Auth\Contracts\Repositories\AuthUserRepositoryInterface;

final class AuthUserRepository implements AuthUserRepositoryInterface
{
    public function findByEmail(string $email): ?User
    {
        return User::query()->where('email', $email)->first();
    }

    public function hasAdministratorRole(User $user): bool
    {
        return $user->roles()->whereIn('name', ['owner', 'admin'])->exists();
    }

    public function hasCustomerRole(User $user): bool
    {
        return $user->roles()->where('name', 'customer')->exists();
    }
}
