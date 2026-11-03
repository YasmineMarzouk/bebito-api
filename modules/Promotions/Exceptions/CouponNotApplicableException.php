<?php

namespace Modules\Promotions\Exceptions;

use Modules\Core\Exceptions\DomainException;
use Symfony\Component\HttpFoundation\Response;

final class CouponNotApplicableException extends DomainException
{
    protected ?int $customErrorCode = 4103;

    public function __construct()
    {
        parent::__construct('This coupon cannot be applied to the order.', Response::HTTP_UNPROCESSABLE_ENTITY);
    }
}
