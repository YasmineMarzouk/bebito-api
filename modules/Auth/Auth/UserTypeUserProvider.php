<?php

namespace Modules\Auth\Auth;

use App\Models\User;
use Illuminate\Auth\EloquentUserProvider;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Hashing\Hasher;
use Modules\Core\Enums\UserTypeEnum;

final class UserTypeUserProvider extends EloquentUserProvider
{
    public function __construct(Hasher $hasher, string $model, private readonly UserTypeEnum $userType)
    {
        parent::__construct($hasher, $model);
    }

    public function retrieveById($identifier): ?Authenticatable
    {
        return $this->allowed(parent::retrieveById($identifier));
    }

    public function retrieveByToken($identifier, #[\SensitiveParameter] $token): ?Authenticatable
    {
        return $this->allowed(parent::retrieveByToken($identifier, $token));
    }

    /** @param array<string, mixed> $credentials */
    public function retrieveByCredentials(#[\SensitiveParameter] array $credentials): ?Authenticatable
    {
        return $this->allowed(parent::retrieveByCredentials($credentials));
    }

    private function allowed(?Authenticatable $user): ?Authenticatable
    {
        if (! $user instanceof User || $user->user_type !== $this->userType) {
            return null;
        }

        return $user;
    }
}
