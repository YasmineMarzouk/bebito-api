<?php

namespace Modules\Auth\Exceptions;

use Modules\Core\Exceptions\DomainException;
use Symfony\Component\HttpFoundation\Response;

final class AccountBlockedException extends DomainException
{
    protected ?int $customErrorCode = 4013;

    public function __construct()
    {
        parent::__construct('This account has been blocked.', Response::HTTP_FORBIDDEN);
    }
}
