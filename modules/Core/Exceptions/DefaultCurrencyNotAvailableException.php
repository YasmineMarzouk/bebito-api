<?php

namespace Modules\Core\Exceptions;

use Symfony\Component\HttpFoundation\Response;

final class DefaultCurrencyNotAvailableException extends DomainException
{
    protected ?int $customErrorCode = 4001;

    public function __construct()
    {
        parent::__construct('The selected default currency is not available.', Response::HTTP_UNPROCESSABLE_ENTITY);
    }
}
