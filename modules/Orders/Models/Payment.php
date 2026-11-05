<?php

namespace Modules\Orders\Models;

use Modules\Core\Models\BaseModel;

class Payment extends BaseModel
{
    protected $casts = ['metadata' => 'array', 'paid_at' => 'datetime'];
}
