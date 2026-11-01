<?php

namespace Modules\Auth\DTOs\Inputs;

final readonly class LoginInput
{
    public function __construct(public string $email, public string $password, public bool $remember) {}

    /** @param array{email:string,password:string,remember?:bool} $data */
    public static function from(array $data): self
    {
        return new self($data['email'], $data['password'], $data['remember'] ?? false);
    }
}
