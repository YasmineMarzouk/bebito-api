<?php

namespace Modules\Promotions\Exceptions;

use Modules\Core\Exceptions\DomainException;
use Symfony\Component\HttpFoundation\Response;

final class CouponCodeAlreadyExistsException extends DomainException
{
    protected ?int $customErrorCode = 4102;

    public function __construct()
    {
        parent::__construct('A coupon with this code already exists.', Response::HTTP_UNPROCESSABLE_ENTITY);
    }
}
