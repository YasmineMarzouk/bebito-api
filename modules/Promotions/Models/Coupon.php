<?php

namespace Modules\Promotions\Models;

use Modules\Core\Models\BaseModel;

final class Coupon extends BaseModel
{
    protected $casts = [
        'percentage' => 'integer',
        'fixed_amount' => 'integer',
        'min_order_amount' => 'integer',
        'usage_limit' => 'integer',
        'usage_count' => 'integer',
        'expires_at' => 'date',
        'is_active' => 'boolean',
    ];
}
