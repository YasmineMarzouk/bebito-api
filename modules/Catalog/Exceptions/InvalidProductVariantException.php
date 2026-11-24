<?php

namespace Modules\Catalog\Exceptions;

use Modules\Core\Exceptions\DomainException;
use Symfony\Component\HttpFoundation\Response;

final class InvalidProductVariantException extends DomainException
{
    protected ?int $customErrorCode = 4403;

    public function __construct()
    {
        parent::__construct('One or more product variants do not belong to this product.', Response::HTTP_UNPROCESSABLE_ENTITY);
    }
}
