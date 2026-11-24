<?php

namespace Modules\Orders\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Catalog\Models\PackVariant;
use Modules\Catalog\Models\ProductVariant;
use Modules\Core\Models\BaseModel;
use Modules\Orders\Enums\OrderItemTypeEnum;

class OrderItem extends BaseModel
{
    protected $casts = [
        'catalog_item_type' => OrderItemTypeEnum::class,
        'variant_attributes' => 'array',
        'pack_snapshot' => 'array',
    ];

    public function productVariant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class);
    }

    public function packVariant(): BelongsTo
    {
        return $this->belongsTo(PackVariant::class);
    }

    public function packComponents(): HasMany
    {
        return $this->hasMany(OrderItemPackComponent::class);
    }
}
