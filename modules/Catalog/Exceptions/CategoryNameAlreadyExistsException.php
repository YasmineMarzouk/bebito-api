<?php

namespace Modules\Catalog\Exceptions;

use Modules\Core\Exceptions\DomainException;
use Symfony\Component\HttpFoundation\Response;

final class CategoryNameAlreadyExistsException extends DomainException
{
    protected ?int $customErrorCode = 4094;

    public function __construct()
    {
        parent::__construct('A category with this French name already exists.', Response::HTTP_CONFLICT);
    }
}
