<?php

namespace Modules\Orders\Models;

use Modules\Core\Models\BaseModel;

final class Order extends BaseModel
{
    protected $casts = [
        'coupon_id' => 'integer',
        'coupon_discount_amount' => 'integer',
        'total_amount' => 'integer',
    ];
}
