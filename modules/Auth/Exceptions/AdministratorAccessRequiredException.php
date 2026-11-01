<?php

namespace Modules\Auth\Exceptions;

use Modules\Core\Exceptions\DomainException;
use Symfony\Component\HttpFoundation\Response;

final class AdministratorAccessRequiredException extends DomainException
{
    protected ?int $customErrorCode = 4031;

    public function __construct()
    {
        parent::__construct('Administrator access is required.', Response::HTTP_FORBIDDEN);
    }
}
