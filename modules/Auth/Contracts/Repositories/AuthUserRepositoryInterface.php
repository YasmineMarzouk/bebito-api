<?php

namespace Modules\Auth\Contracts\Repositories;

use App\Models\User;

interface AuthUserRepositoryInterface
{
    public function findByEmail(string $email): ?User;

    public function hasAdministratorRole(User $user): bool;
}
