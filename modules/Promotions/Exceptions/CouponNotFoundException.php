<?php

namespace Modules\Promotions\Exceptions;

use Modules\Core\Exceptions\DomainException;
use Symfony\Component\HttpFoundation\Response;

final class CouponNotFoundException extends DomainException
{
    protected ?int $customErrorCode = 4101;

    public function __construct()
    {
        parent::__construct('The requested coupon was not found.', Response::HTTP_NOT_FOUND);
    }
}
