<?php

namespace Modules\Promotions\Repositories;

use Illuminate\Database\Eloquent\Collection;
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

    public function find(int $id): ?Coupon
    {
        return Coupon::query()->find($id);
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

    public function incrementUsage(int $couponId): void
    {
        Coupon::query()->whereKey($couponId)->increment('usage_count');
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
