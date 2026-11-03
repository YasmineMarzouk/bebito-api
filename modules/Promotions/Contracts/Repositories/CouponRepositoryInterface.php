<?php

namespace Modules\Promotions\Contracts\Repositories;

use Illuminate\Database\Eloquent\Collection;
use Modules\Promotions\DTOs\Inputs\CreateCouponInput;
use Modules\Promotions\DTOs\Inputs\UpdateCouponInput;
use Modules\Promotions\Models\Coupon;

interface CouponRepositoryInterface
{
    /** @return Collection<int, Coupon> */
    public function all(): Collection;

    public function find(int $id): ?Coupon;

    public function create(CreateCouponInput $input): Coupon;

    public function update(Coupon $coupon, UpdateCouponInput $input): Coupon;

    public function delete(Coupon $coupon): void;

    public function codeExists(string $code, ?int $excludingCouponId = null): bool;

    public function incrementUsage(int $couponId): void;

    public function decrementUsage(int $couponId): void;
}
