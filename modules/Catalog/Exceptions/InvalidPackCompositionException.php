<?php

namespace Modules\Catalog\Exceptions;

use Modules\Core\Exceptions\DomainException;
use Symfony\Component\HttpFoundation\Response;

final class InvalidPackCompositionException extends DomainException
{
    protected ?int $customErrorCode = 4226;

    public function __construct()
    {
        parent::__construct('The pack composition or its variant mappings are invalid.', Response::HTTP_UNPROCESSABLE_ENTITY);
    }
}
