<?php

namespace Modules\Catalog\Exceptions;

use Modules\Core\Exceptions\DomainException;
use Symfony\Component\HttpFoundation\Response;

final class ProductVariantUnavailableException extends DomainException
{
    protected ?int $customErrorCode = 4223;

    public function __construct()
    {
        parent::__construct('One or more selected product variants are no longer available.', Response::HTTP_UNPROCESSABLE_ENTITY);
    }
}
