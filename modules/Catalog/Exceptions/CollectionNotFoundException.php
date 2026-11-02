<?php

namespace Modules\Catalog\Exceptions;

use Modules\Core\Exceptions\DomainException;
use Symfony\Component\HttpFoundation\Response;

final class CollectionNotFoundException extends DomainException
{
    protected ?int $customErrorCode = 4043;

    public function __construct()
    {
        parent::__construct('The requested collection was not found.', Response::HTTP_NOT_FOUND);
    }
}
