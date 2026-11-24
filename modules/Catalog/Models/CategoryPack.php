<?php

namespace Modules\Catalog\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;

class CategoryPack extends Pivot
{
    protected $table = 'category_pack';

    public $incrementing = false;
}
