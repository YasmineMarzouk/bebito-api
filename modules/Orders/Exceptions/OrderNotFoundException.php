<?php

namespace Modules\Orders\Exceptions;

use Modules\Core\Exceptions\DomainException;
use Symfony\Component\HttpFoundation\Response;

final class OrderNotFoundException extends DomainException
{
    protected ?int $customErrorCode = 4201;

    public function __construct()
    {
        parent::__construct('The requested order was not found.', Response::HTTP_NOT_FOUND);
    }
}
