<?php

namespace Modules\Promotions\UseCases;

use Modules\Promotions\Contracts\Repositories\CouponRepositoryInterface;
use Modules\Promotions\DTOs\Inputs\CreateCouponInput;
use Modules\Promotions\DTOs\Outputs\CouponOutput;
use Modules\Promotions\Exceptions\CouponCodeAlreadyExistsException;

final class CreateCouponUseCase
{
    public function __construct(private readonly CouponRepositoryInterface $coupons) {}

    public function execute(CreateCouponInput $input): CouponOutput
    {
        if ($this->coupons->codeExists($input->code)) {
            throw new CouponCodeAlreadyExistsException;
        }

        return CouponOutput::fromModel($this->coupons->create($input));
    }
}
