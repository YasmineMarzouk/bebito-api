<?php

namespace Modules\Catalog\Exceptions;

use Modules\Core\Exceptions\DomainException;
use Symfony\Component\HttpFoundation\Response;

final class HomepageCategoriesMustBeActiveException extends DomainException
{
    protected ?int $customErrorCode = 4221;

    public function __construct()
    {
        parent::__construct('Only active categories can be displayed on the homepage.', Response::HTTP_UNPROCESSABLE_ENTITY);
    }
}
