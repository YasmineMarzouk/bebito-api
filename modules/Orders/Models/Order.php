<?php

namespace Modules\Orders\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Core\Models\BaseModel;
use Modules\Orders\Enums\OrderStatusEnum;
use Modules\Orders\Enums\OrderTypeEnum;
use Modules\Orders\Enums\PaymentMethodEnum;

final class Order extends BaseModel
{
    protected $casts = [
        'status' => OrderStatusEnum::class,
        'order_type' => OrderTypeEnum::class,
        'payment_method_code' => PaymentMethodEnum::class,
        'coupon_id' => 'integer',
        'coupon_discount_amount' => 'integer',
        'subtotal_amount' => 'integer',
        'shipping_amount' => 'integer',
        'total_amount' => 'integer',
    ];

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function addresses(): HasMany
    {
        return $this->hasMany(OrderAddress::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function statusHistories(): HasMany
    {
        return $this->hasMany(OrderStatusHistory::class);
    }
}
