<?php

namespace Modules\Promotions\Contracts\Repositories;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Modules\Promotions\DTOs\Inputs\CreateCouponInput;
use Modules\Promotions\DTOs\Inputs\UpdateCouponInput;
use Modules\Promotions\Models\Coupon;

interface CouponRepositoryInterface
{
    /** @return Collection<int, Coupon> */
    public function all(): Collection;

    /** @return LengthAwarePaginator<int, Coupon> */
    public function paginate(int $perPage, int $page): LengthAwarePaginator;

    public function find(int $id): ?Coupon;

    public function findByCode(string $code): ?Coupon;

    public function findForUpdate(int $id): ?Coupon;

    public function findByCodeForUpdate(string $code): ?Coupon;

    public function create(CreateCouponInput $input): Coupon;

    public function update(Coupon $coupon, UpdateCouponInput $input): Coupon;

    public function delete(Coupon $coupon): void;

    public function codeExists(string $code, ?int $excludingCouponId = null): bool;

    public function incrementUsage(int $couponId): bool;

    public function decrementUsage(int $couponId): void;
}
