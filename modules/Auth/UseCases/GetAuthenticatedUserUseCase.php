<?php

namespace Modules\Auth\UseCases;

use App\Models\User;

final class GetAuthenticatedUserUseCase
{
    public function execute(User $user): User
    {
        return $user;
    }
}
