<?php

namespace Modules\Auth\UseCases;

use App\Models\User;
use Modules\Auth\Contracts\Repositories\AuthUserRepositoryInterface;
use Modules\Auth\Exceptions\AccountBlockedException;
use Modules\Auth\Exceptions\CustomerAccessRequiredException;

final class GetAuthenticatedCustomerUseCase
{
    public function __construct(private readonly AuthUserRepositoryInterface $users) {}

    public function execute(User $user): User
    {
        if ($user->is_blocked) {
            throw new AccountBlockedException;
        }

        if (! $this->users->hasCustomerRole($user)) {
            throw new CustomerAccessRequiredException;
        }

        return $user;
    }
}
