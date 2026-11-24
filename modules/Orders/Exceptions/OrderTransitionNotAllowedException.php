<?php

namespace Modules\Orders\Exceptions;

use Modules\Core\Exceptions\DomainException;
use Symfony\Component\HttpFoundation\Response;

final class OrderTransitionNotAllowedException extends DomainException
{
    protected ?int $customErrorCode = 4205;

    public function __construct()
    {
        parent::__construct('This order transition is not allowed.', Response::HTTP_UNPROCESSABLE_ENTITY);
    }
}
