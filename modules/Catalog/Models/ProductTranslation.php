<?php

namespace Modules\Catalog\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Core\Models\BaseModel;

class ProductTranslation extends BaseModel
{
    protected $fillable = ['product_id', 'locale_code', 'name', 'slug', 'short_description', 'description', 'meta_title', 'meta_description'];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
