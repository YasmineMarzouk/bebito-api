<?php

namespace Modules\Catalog\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Core\Models\BaseModel;

final class Bestseller extends BaseModel
{
    use SoftDeletes;

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
