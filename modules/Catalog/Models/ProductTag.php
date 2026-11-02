<?php

namespace Modules\Catalog\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;

class ProductTag extends Pivot
{
    protected $table = 'product_tag';

    public $incrementing = false;
}
