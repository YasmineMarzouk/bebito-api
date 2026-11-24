<?php

namespace Modules\Orders\Models;

use Modules\Core\Models\BaseModel;
use Modules\Orders\Enums\AddressTypeEnum;

class OrderAddress extends BaseModel
{
    protected $casts = ['type' => AddressTypeEnum::class];
}
