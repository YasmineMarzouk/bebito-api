<?php

namespace Modules\Orders\Models;

use Modules\Core\Models\BaseModel;
use Modules\Orders\Enums\OrderStatusEnum;

class OrderStatusHistory extends BaseModel
{
    protected $casts = ['status' => OrderStatusEnum::class];
}
