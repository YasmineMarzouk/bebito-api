<?php

namespace Modules\Catalog\Exceptions;

use Modules\Core\Exceptions\DomainException;
use Symfony\Component\HttpFoundation\Response;

final class ProductNotFoundException extends DomainException
{
    protected ?int $customErrorCode = 4401;

    public function __construct()
    {
        parent::__construct('The requested product was not found.', Response::HTTP_NOT_FOUND);
    }
}
