<?php

namespace Modules\Catalog\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;

class CategoryProduct extends Pivot
{
    protected $table = 'category_product';

    public $incrementing = false;
}
