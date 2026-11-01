<?php

namespace Modules\Auth\Exceptions;

use Modules\Core\Exceptions\DomainException;
use Symfony\Component\HttpFoundation\Response;

final class InvalidCredentialsException extends DomainException
{
    protected ?int $customErrorCode = 4011;

    public function __construct()
    {
        parent::__construct('The provided credentials are incorrect.', Response::HTTP_UNAUTHORIZED);
    }
}
