<?php

namespace Modules\Catalog\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Modules\Core\Models\Asset;
use Modules\Core\Models\BaseModel;

class Category extends BaseModel
{
    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function translations(): HasMany
    {
        return $this->hasMany(CategoryTranslation::class);
    }

    public function mainCategories(): HasMany
    {
        return $this->hasMany(MainCategory::class);
    }

    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class)->using(CategoryProduct::class)->withTimestamps();
    }

    public function packs(): BelongsToMany
    {
        return $this->belongsToMany(Pack::class)->using(CategoryPack::class)->withTimestamps();
    }

    public function primaryAsset(): MorphOne
    {
        return $this->morphOne(Asset::class, 'resource')->where('primary_image', true);
    }

    public function getMorphClass(): string
    {
        return 'category';
    }

    /** @return \Closure(BelongsTo<Category, MainCategory>): void */
    public static function categoryDetails(): \Closure
    {
        return function (BelongsTo $query): void {
            $query->with(['translations', 'primaryAsset'])->withCount('products');
        };
    }
}
