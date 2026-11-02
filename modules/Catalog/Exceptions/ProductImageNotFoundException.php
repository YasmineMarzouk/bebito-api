<?php

namespace Modules\Catalog\Exceptions;

use Modules\Core\Exceptions\DomainException;
use Symfony\Component\HttpFoundation\Response;

final class ProductImageNotFoundException extends DomainException
{
    protected ?int $customErrorCode = 4402;

    public function __construct()
    {
        parent::__construct('The requested product image was not found.', Response::HTTP_NOT_FOUND);
    }
}
