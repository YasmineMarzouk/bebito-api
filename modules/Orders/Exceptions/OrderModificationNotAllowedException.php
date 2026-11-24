<?php

namespace Modules\Orders\Exceptions;

use Modules\Core\Exceptions\DomainException;
use Symfony\Component\HttpFoundation\Response;

final class OrderModificationNotAllowedException extends DomainException
{
    protected ?int $customErrorCode = 4204;

    public function __construct()
    {
        parent::__construct('Only pending orders can be modified.', Response::HTTP_UNPROCESSABLE_ENTITY);
    }
}
