<?php

namespace Modules\Auth\UseCases;

use App\Models\User;
use Modules\Auth\Contracts\Repositories\AuthUserRepositoryInterface;
use Modules\Auth\Exceptions\AdministratorAccessRequiredException;

final class GetAuthenticatedUserUseCase
{
    public function __construct(private readonly AuthUserRepositoryInterface $users) {}

    public function execute(User $user): User
    {
        if (! $this->users->hasAdministratorRole($user)) {
            throw new AdministratorAccessRequiredException;
        }

        return $user;
    }
}
