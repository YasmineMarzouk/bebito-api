<?php

namespace Modules\Catalog\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Core\Models\BaseModel;

class Size extends BaseModel
{
    protected $fillable = ['name', 'sort_order', 'is_default'];

    protected function casts(): array
    {
        return ['sort_order' => 'integer', 'is_default' => 'boolean'];
    }

    public function productVariants(): HasMany
    {
        return $this->hasMany(ProductVariant::class);
    }
}
