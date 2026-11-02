<?php

namespace Modules\Catalog\Exceptions;

use Modules\Core\Exceptions\DomainException;
use Symfony\Component\HttpFoundation\Response;

final class CategorySlugAlreadyExistsException extends DomainException
{
    protected ?int $customErrorCode = 4092;

    public function __construct()
    {
        parent::__construct('A category with this slug already exists for one of the selected locales.', Response::HTTP_CONFLICT);
    }
}
