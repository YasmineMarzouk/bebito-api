<?php

namespace Modules\Catalog\Exceptions;

use Modules\Core\Exceptions\DomainException;
use Symfony\Component\HttpFoundation\Response;

final class CategoryNotFoundException extends DomainException
{
    protected ?int $customErrorCode = 4042;

    public function __construct()
    {
        parent::__construct('The requested category was not found.', Response::HTTP_NOT_FOUND);
    }
}
