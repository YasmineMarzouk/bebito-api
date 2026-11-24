<?php

namespace Modules\Orders\Models;

use Modules\Core\Models\BaseModel;
use Modules\Orders\Enums\PaymentMethodEnum;
use Modules\Orders\Enums\PaymentStatusEnum;

class Payment extends BaseModel
{
    protected $casts = [
        'method' => PaymentMethodEnum::class,
        'status' => PaymentStatusEnum::class,
        'metadata' => 'array',
        'paid_at' => 'datetime',
    ];
}
