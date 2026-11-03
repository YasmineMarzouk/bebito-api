<?php

namespace Modules\Customer\Exceptions;

use Modules\Core\Exceptions\DomainException;
use Symfony\Component\HttpFoundation\Response;

final class CustomerEmailAlreadyExistsException extends DomainException
{
    protected ?int $customErrorCode = 4302;

    public function __construct()
    {
        parent::__construct('A customer with this email address already exists.', Response::HTTP_UNPROCESSABLE_ENTITY);
    }
}
