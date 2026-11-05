<?php

namespace Modules\Catalog\Models;

use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Modules\Core\Models\Asset;
use Modules\Core\Models\BaseModel;

class Category extends BaseModel
{
    protected $casts = [
        'is_active' => 'boolean',
        'is_homepage_featured' => 'boolean',
    ];

    public function translations(): HasMany
    {
        return $this->hasMany(CategoryTranslation::class);
    }

    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class)->using(CategoryProduct::class)->withTimestamps();
    }

    public function primaryAsset(): MorphOne
    {
        return $this->morphOne(Asset::class, 'resource')->where('is_primary', true);
    }

    public function getMorphClass(): string
    {
        return 'category';
    }
}
