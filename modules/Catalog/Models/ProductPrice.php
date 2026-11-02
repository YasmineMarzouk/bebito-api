<?php

namespace Modules\Catalog\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Core\Models\BaseModel;

class ProductPrice extends BaseModel
{
    protected $fillable = ['product_variant_id', 'currency_code', 'price_amount', 'compare_at_price_amount'];

    public function variant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class, 'product_variant_id');
    }
}
