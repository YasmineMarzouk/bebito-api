<?php

namespace Modules\Catalog\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Modules\Core\Models\BaseModel;

class ProductVariant extends BaseModel
{
    protected $fillable = ['product_id', 'size_id', 'sku', 'attributes', 'is_default', 'is_active'];

    protected function casts(): array
    {
        return ['attributes' => 'array', 'is_default' => 'boolean', 'is_active' => 'boolean'];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function size(): BelongsTo
    {
        return $this->belongsTo(Size::class);
    }

    public function prices(): HasMany
    {
        return $this->hasMany(ProductPrice::class);
    }

    public function inventoryItem(): HasOne
    {
        return $this->hasOne(InventoryItem::class);
    }

    public function packVariantMappings(): HasMany
    {
        return $this->hasMany(PackVariantComponentVariant::class);
    }
}
