<?php

namespace Modules\Catalog\Models;

use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Modules\Core\Models\Asset;
use Modules\Core\Models\BaseModel;

class Product extends BaseModel
{
    protected $fillable = [
        'status',
        'gender',
        'sku',
        'price_amount',
        'compare_at_price_amount',
        'discount_percentage',
        'stock_quantity',
        'low_stock_threshold',
    ];

    protected function casts(): array
    {
        return [
            'price_amount' => 'integer',
            'compare_at_price_amount' => 'integer',
            'discount_percentage' => 'float',
            'stock_quantity' => 'integer',
            'low_stock_threshold' => 'integer',
        ];
    }

    public function translations(): HasMany
    {
        return $this->hasMany(ProductTranslation::class);
    }

    public function variants(): HasMany
    {
        return $this->hasMany(ProductVariant::class);
    }

    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(Category::class)->using(CategoryProduct::class)->withTimestamps();
    }

    public function collections(): BelongsToMany
    {
        return $this->belongsToMany(Collection::class)->using(CollectionProduct::class)->withPivot('sort_order')->withTimestamps();
    }

    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(Tag::class)->using(ProductTag::class)->withTimestamps();
    }

    public function assets(): MorphMany
    {
        return $this->morphMany(Asset::class, 'resource')->orderBy('sort_order')->orderBy('id');
    }

    public function getMorphClass(): string
    {
        return 'product';
    }
}
