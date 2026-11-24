<?php

namespace Modules\Promotions\Repositories;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Modules\Promotions\Contracts\Repositories\CouponRepositoryInterface;
use Modules\Promotions\DTOs\Inputs\CreateCouponInput;
use Modules\Promotions\DTOs\Inputs\UpdateCouponInput;
use Modules\Promotions\Models\Coupon;

final class CouponRepository implements CouponRepositoryInterface
{
    public function all(): Collection
    {
        return Coupon::query()->orderByDesc('id')->get();
    }

    public function paginate(int $perPage, int $page): LengthAwarePaginator
    {
        return Coupon::query()->orderByDesc('id')->paginate($perPage, ['*'], 'page', $page);
    }

    public function find(int $id): ?Coupon
    {
        return Coupon::query()->find($id);
    }

    public function findByCode(string $code): ?Coupon
    {
        return Coupon::query()->where('code', $code)->first();
    }

    public function findForUpdate(int $id): ?Coupon
    {
        return Coupon::query()->lockForUpdate()->find($id);
    }

    public function findByCodeForUpdate(string $code): ?Coupon
    {
        return Coupon::query()->where('code', $code)->lockForUpdate()->first();
    }

    public function create(CreateCouponInput $input): Coupon
    {
        $coupon = Coupon::query()->create($this->attributes($input));

        return $coupon->refresh();
    }

    public function update(Coupon $coupon, UpdateCouponInput $input): Coupon
    {
        $coupon->update($this->attributes($input));

        return $coupon->refresh();
    }

    public function delete(Coupon $coupon): void
    {
        $coupon->delete();
    }

    public function codeExists(string $code, ?int $excludingCouponId = null): bool
    {
        return Coupon::query()
            ->where('code', $code)
            ->when($excludingCouponId !== null, fn ($query) => $query->where('id', '!=', $excludingCouponId))
            ->exists();
    }

    public function incrementUsage(int $couponId): bool
    {
        return Coupon::query()
            ->whereKey($couponId)
            ->where('is_active', true)
            ->where(function ($query): void {
                $query->whereNull('usage_limit')->orWhereColumn('usage_count', '<', 'usage_limit');
            })
            ->increment('usage_count') === 1;
    }

    public function decrementUsage(int $couponId): void
    {
        Coupon::query()->whereKey($couponId)->where('usage_count', '>', 0)->decrement('usage_count');
    }

    /** @return array<string, bool|int|string|null> */
    private function attributes(CreateCouponInput|UpdateCouponInput $input): array
    {
        return [
            'code' => $input->code,
            'type' => $input->type,
            'percentage' => $input->percentage,
            'fixed_amount' => $input->fixedAmount,
            'min_order_amount' => $input->minOrderAmount,
            'usage_limit' => $input->usageLimit,
            'expires_at' => $input->expiresAt,
            'is_active' => $input->isActive,
        ];
    }
}
