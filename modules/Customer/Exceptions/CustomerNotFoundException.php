<?php

namespace Modules\Customer\Exceptions;

use Modules\Core\Exceptions\DomainException;
use Symfony\Component\HttpFoundation\Response;

final class CustomerNotFoundException extends DomainException
{
    protected ?int $customErrorCode = 4301;

    public function __construct()
    {
        parent::__construct('The requested customer was not found.', Response::HTTP_NOT_FOUND);
    }
}
