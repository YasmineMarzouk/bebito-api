<?php

namespace Modules\Catalog\Exceptions;

use Modules\Core\Exceptions\DomainException;
use Symfony\Component\HttpFoundation\Response;

final class HomepageProductsMustBePublishedException extends DomainException
{
    protected ?int $customErrorCode = 4222;

    public function __construct()
    {
        parent::__construct('Only published products can be displayed in Bestsellers.', Response::HTTP_UNPROCESSABLE_ENTITY);
    }
}
