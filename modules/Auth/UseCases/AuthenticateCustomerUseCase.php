<?php

namespace Modules\Auth\UseCases;

use App\Models\User;
use Illuminate\Contracts\Hashing\Hasher;
use Modules\Auth\Contracts\Repositories\AuthUserRepositoryInterface;
use Modules\Auth\DTOs\Inputs\LoginInput;
use Modules\Auth\Exceptions\AccountBlockedException;
use Modules\Auth\Exceptions\CustomerAccessRequiredException;
use Modules\Auth\Exceptions\InvalidCredentialsException;

final class AuthenticateCustomerUseCase
{
    public function __construct(private readonly AuthUserRepositoryInterface $users, private readonly Hasher $hasher) {}

    public function execute(LoginInput $input): User
    {
        $user = $this->users->findByEmail($input->email);

        if ($user === null || ! $this->hasher->check($input->password, $user->password)) {
            throw new InvalidCredentialsException;
        }

        if ($user->is_blocked) {
            throw new AccountBlockedException;
        }

        if (! $this->users->hasCustomerRole($user)) {
            throw new CustomerAccessRequiredException;
        }

        return $user;
    }
}
