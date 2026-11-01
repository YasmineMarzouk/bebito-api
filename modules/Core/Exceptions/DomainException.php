<?php

namespace Modules\Core\Exceptions;

use RuntimeException;

abstract class DomainException extends RuntimeException
{
    protected ?int $customErrorCode = null;

    public function customErrorCode(): ?int
    {
        return $this->customErrorCode;
    }
}
