<?php

namespace Modules\Catalog\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Core\Models\BaseModel;

final class MainCategory extends BaseModel
{
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }
}
