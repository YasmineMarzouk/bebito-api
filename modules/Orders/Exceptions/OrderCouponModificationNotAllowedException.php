<?php

namespace Modules\Orders\Exceptions;

use Modules\Core\Exceptions\DomainException;
use Symfony\Component\HttpFoundation\Response;

final class OrderCouponModificationNotAllowedException extends DomainException
{
    protected ?int $customErrorCode = 4203;

    public function __construct()
    {
        parent::__construct('Coupons can only be changed while an order is pending.', Response::HTTP_UNPROCESSABLE_ENTITY);
    }
}
