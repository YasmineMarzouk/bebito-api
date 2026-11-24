<?php

namespace Modules\Orders\Exceptions;

use Modules\Core\Exceptions\DomainException;
use Symfony\Component\HttpFoundation\Response;

final class CheckoutIdempotencyConflictException extends DomainException
{
    protected ?int $customErrorCode = 4201;

    public function __construct()
    {
        parent::__construct('This checkout key has already been used with different details.', Response::HTTP_UNPROCESSABLE_ENTITY);
    }
}
