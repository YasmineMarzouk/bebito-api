<?php

namespace Modules\Orders\Contracts\Repositories;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Modules\Catalog\Contracts\ValueObjects\ReservedProductVariant;
use Modules\Orders\Models\Order;
use Modules\Promotions\Contracts\ValueObjects\CouponApplication;

interface OrderRepositoryInterface
{
    /** @return Collection<int, Order> */
    public function all(?int $customerId = null): Collection;

    /** @return LengthAwarePaginator<int, Order> */
    public function paginateForAdmin(?int $customerId, int $perPage, int $page): LengthAwarePaginator;

    /** @return LengthAwarePaginator<int, Order> */
    public function paginateForCustomer(int $customerId, int $page): LengthAwarePaginator;

    public function find(int $id): ?Order;

    public function findForUpdate(int $id): ?Order;

    public function findForManagementForUpdate(int $id): ?Order;

    public function findByIdempotencyKeyForUpdate(string $idempotencyKey): ?Order;

    public function applyCoupon(Order $order, CouponApplication $coupon, int $grossAmount): Order;

    /**
     * @param  array<int, ReservedProductVariant>  $items
     * @param  array{firstName: string, lastName: string, email: string, phone: string, address: string, city: string, postalCode: ?string, deliveryNotes: ?string}  $customer
     */
    public function createCheckout(?int $userId, array $customer, array $items, int $shippingAmount, string $currencyCode, string $idempotencyKey, string $idempotencyFingerprint, ?CouponApplication $coupon = null): Order;
}
