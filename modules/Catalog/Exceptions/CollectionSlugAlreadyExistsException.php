<?php

namespace Modules\Catalog\Exceptions;

use Modules\Core\Exceptions\DomainException;
use Symfony\Component\HttpFoundation\Response;

final class CollectionSlugAlreadyExistsException extends DomainException
{
    protected ?int $customErrorCode = 4093;

    public function __construct()
    {
        parent::__construct('A collection with this slug already exists for one of the selected locales.', Response::HTTP_CONFLICT);
    }
}
