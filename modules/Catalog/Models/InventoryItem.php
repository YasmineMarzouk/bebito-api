<?php

namespace Modules\Catalog\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Core\Models\BaseModel;

class InventoryItem extends BaseModel
{
    protected $fillable = ['product_variant_id', 'quantity', 'low_stock_threshold'];

    protected function casts(): array
    {
        return ['quantity' => 'integer', 'low_stock_threshold' => 'integer'];
    }

    public function variant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class, 'product_variant_id');
    }
}
