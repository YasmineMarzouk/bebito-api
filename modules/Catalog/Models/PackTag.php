<?php

namespace Modules\Catalog\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;

class PackTag extends Pivot
{
    protected $table = 'pack_tag';

    public $incrementing = false;
}
