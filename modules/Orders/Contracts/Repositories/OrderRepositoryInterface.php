<?php

namespace Modules\Orders\Contracts\Repositories;

use Illuminate\Database\Eloquent\Collection;
use Modules\Catalog\Contracts\ValueObjects\ReservedProductVariant;
use Modules\Orders\Models\Order;
use Modules\Promotions\Contracts\ValueObjects\CouponApplication;

interface OrderRepositoryInterface
{
    /** @return Collection<int, Order> */
    public function all(?int $customerId = null): Collection;

    public function find(int $id): ?Order;

    public function applyCoupon(Order $order, CouponApplication $coupon, int $grossAmount): Order;

    /**
     * @param  array<int, ReservedProductVariant>  $items
     * @param  array{firstName: string, lastName: string, email: string, phone: string, address: string, city: string, deliveryNotes: ?string}  $customer
     */
    public function createCheckout(?int $userId, array $customer, array $items, int $shippingAmount, string $currencyCode): Order;
}
