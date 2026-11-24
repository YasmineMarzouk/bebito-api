<?php

namespace Modules\Orders\Exceptions;

use Modules\Core\Exceptions\DomainException;
use Symfony\Component\HttpFoundation\Response;

final class CheckoutUnavailableException extends DomainException
{
    protected ?int $customErrorCode = 4202;

    public function __construct()
    {
        parent::__construct('Checkout is temporarily unavailable.', Response::HTTP_SERVICE_UNAVAILABLE);
    }
}
