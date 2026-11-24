<?php

namespace Modules\Orders\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Catalog\Models\Product;
use Modules\Catalog\Models\ProductVariant;
use Modules\Core\Models\BaseModel;

class OrderItemPackComponent extends BaseModel
{
    protected $fillable = [
        'order_item_id',
        'product_id',
        'product_variant_id',
        'product_name',
        'product_sku',
        'variant_sku',
        'variant_attributes',
        'quantity_per_pack',
        'quantity_consumed',
        'unit_price_amount',
        'total_amount',
        'component_snapshot',
    ];

    protected function casts(): array
    {
        return [
            'variant_attributes' => 'array',
            'quantity_per_pack' => 'integer',
            'quantity_consumed' => 'integer',
            'unit_price_amount' => 'integer',
            'total_amount' => 'integer',
            'component_snapshot' => 'array',
        ];
    }

    public function orderItem(): BelongsTo
    {
        return $this->belongsTo(OrderItem::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function productVariant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class);
    }
}
