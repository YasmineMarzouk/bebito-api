<?php

namespace Modules\Core\Exceptions;

use RuntimeException;
use Symfony\Component\HttpFoundation\Response;

final class ExternalServiceException extends RuntimeException
{
    public function __construct()
    {
        parent::__construct('The translation service is temporarily unavailable.', Response::HTTP_SERVICE_UNAVAILABLE);
    }
}
