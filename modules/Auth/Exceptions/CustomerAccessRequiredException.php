<?php

namespace Modules\Auth\Exceptions;

use Modules\Core\Exceptions\DomainException;
use Symfony\Component\HttpFoundation\Response;

final class CustomerAccessRequiredException extends DomainException
{
    protected ?int $customErrorCode = 4032;

    public function __construct()
    {
        parent::__construct('Customer access is required.', Response::HTTP_FORBIDDEN);
    }
}
