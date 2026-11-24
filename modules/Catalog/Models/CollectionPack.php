<?php

namespace Modules\Catalog\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;

class CollectionPack extends Pivot
{
    protected $table = 'collection_pack';

    public $incrementing = false;
}
