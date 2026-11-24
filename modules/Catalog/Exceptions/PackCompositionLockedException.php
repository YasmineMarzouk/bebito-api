<?php

namespace Modules\Catalog\Exceptions;

use Modules\Core\Exceptions\DomainException;
use Symfony\Component\HttpFoundation\Response;

final class PackCompositionLockedException extends DomainException
{
    protected ?int $customErrorCode = 4225;

    public function __construct()
    {
        parent::__construct('A pack composition cannot be changed after its first publication.', Response::HTTP_CONFLICT);
    }
}
