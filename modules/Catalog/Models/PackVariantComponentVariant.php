<?php

namespace Modules\Catalog\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Core\Models\BaseModel;

class PackVariantComponentVariant extends BaseModel
{
    protected $fillable = ['pack_variant_id', 'pack_component_id', 'product_variant_id'];

    public function packVariant(): BelongsTo
    {
        return $this->belongsTo(PackVariant::class);
    }

    public function packComponent(): BelongsTo
    {
        return $this->belongsTo(PackComponent::class);
    }

    public function productVariant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class);
    }
}
